<?php

namespace App\Libraries;

use CodeIgniter\Database\Exceptions\DatabaseException;
use Config\Database;
use Config\Services;
use Exception;

/**
 * Класс для работы с социальной сетью ВК
 */
class Vk
{
    /*
     * Имя социальной сети
     */
    public string $soc_name;

    /*
     * Секретный ключ приложения
     */
    public string $secret_key;

    /*
     * Сервисный ключ приложения
     */
    public string $service_key;

    /*
     * Версия API
     */
    public float $api_version;

    /*
     * URL API
     */
    public string $api_url;

    /*
     * Конструктор
     */
    public function __construct($config)
    {
        $this->secret_key = $config->secret_key;
        $this->api_version = $config->api_version;
        $this->api_url = $config->api_url;
        $this->service_key = $config->service_key;
        $this->soc_name = $config->soc_name;
    }

    /*
     * Проверка подписи при авторизации в приложении
     */
    public function verifyKey($params)
    {
        if (isset($params['api_id'], $params['viewer_id'], $params['auth_key']) &&
            md5($params['api_id'] . '_' . $params['viewer_id'] . '_' . $this->secret_key) === $params['auth_key']) {
            return $params;
        } else {
            return ['error' => 'Неверная подпись запроса' . (isset($params['viewer_id']) ? ', viewer_id: ' . $params['viewer_id'] : '')];
        }
    }

    /*
     * Проверка подписи входящих параметров платёжки
     */
    public function checkSignature($params, $payments_params)
    {
        foreach ($payments_params as $param) {
            if (!isset($params[$param])) {
                return $this->setPaymentError(11,'В запросе отсутствует обязательный параметр: ' . $param . (isset($params['user_id']) ? " (user_id: " . $params['user_id'] . ")" : ""));
            }
        }
        $sig = $params['sig'];
        unset($params['sig']);
        ksort($params);
        $str = '';
        foreach ($params as $k => $v) {
            $str .= $k . '=' . $v;
        }
        if ($sig != md5($str . $this->secret_key)) {
            return $this->setPaymentError(10,'Несовпадение вычисленной и переданной подписи запроса' . (isset($params['user_id']) ? " (user_id: " . $params['user_id'] . ")" : ""));
        }
        return $params;
    }

    /*
     * Получение информации о продукте
     */
    public function checkProduct($params, $products)
    {
        if (!isset($products[$params['item']])) {
            return $this->setPaymentError(20,'Товар недоступен' . (isset($params['user_id']) ? " (user_id: " . $params['user_id'] . ")" : ""));
        }
        if (in_array($params['notification_type'], ['get_item', 'get_item_test'])) {
            return ['response' => ['item_id' => $params['item'], 'title' => $products[$params['item']]['title'], 'price' => $products[$params['item']]['prices'][$this->soc_name]]];
        }
        if (in_array($params['notification_type'], ['order_status_change_test', 'order_status_change'])) {
            return $params;
        }
        return $this->setPaymentError(1,'Неизвестный notification_type');
    }

    /*
     * Проверка наличия платежа
     */
    public function checkPayment($params, $payment_table)
    {
        $db = Database::connect();
        try {
            $result = $db->table($payment_table)->select('order_id')->where('order_id', $params['order_id'])->where('order_id > ', 0)->get()->getRow();
        } catch (DatabaseException $e) {
            return $this->setPaymentError(1,'Ошибка проверки платежа: ' . $e->getMessage());
        } finally {
            $db->close();
        }
        $order_id = $result->order_id ?? 0;
        if ($order_id > 0) {
            return $this->setPaymentError(1,'Платёж уже был совершён, order_id: ' . $order_id);
        } else {
            return $order_id;
        }
    }

    /*
     * Выставление ошибки
     */
    public function setPaymentError($error_code, $error_msg, $critical = true): array
    {
        return [ 'error' => [ 'error_code' => $error_code, 'error_msg' => $error_msg, 'critical' => $critical ] ];
    }

    /*
     * Получение идентификатора пользователя по платёжным данным
    */
    public function getUserIdByPaymentParams($params)
    {
        return $params['user_id'];
    }

    /*
     * Отправка запроса API
     */
    private function sendRequest($api_method, $query = [], $method = 'GET', $timeout = 5)
    {
        try {
            $client = Services::curlrequest();
            $url = $this->api_url . '/' . $api_method;
            $response = $client->request($method, $url, ['query' => $query, 'timeout' => $timeout]);
            return json_decode($response->getBody(), true);
        } catch (Exception $e) {
            return ['error' => 'Ошибка отправки запроса (' . $e->getMessage() . ')'];
        }
    }

    /*
     * Получение информации о пользователе
     */
    public function getUserInfo($params): array
    {
        $query = [
            'user_id' => $params['viewer_id'],
            'fields' => 'country,city,sex,bdate,photo_200,photo_100,photo_200_orig,is_closed',
            'lang' => 'ru',
            'v' => $this->api_version,
            'access_token' => $this->service_key
        ];
        $attempts = 0;
        do {
            $result = $this->sendRequest('users.get', $query);
            if (isset($result['response'][0]['id'])) {
                return [
                    'id' => $result['response'][0]['id'],
                    'first_name' => $result['response'][0]['first_name'] ?? 'Игрок',
                    'last_name' => $result['response'][0]['last_name'] ?? 'Игрок',
                    'city' => $result['response'][0]['city']['title'] ?? '',
                    'country' => $result['response'][0]['country']['title'] ?? '',
                    'male' => isset($result['response'][0]['sex']) && $result['response'][0]['sex'] != 1 ? 1 : 0,
                    'small_photo' => $result['response'][0]['photo_100'] ?? '',
                    'big_photo' => $result['response'][0]['photo_200'] ?? '',
                    'b_date' => $result['response'][0]['bdate'] ?? '0000-00-00'
                ];
            } else {
                usleep(1000);
                $attempts++;
            }
        } while ($attempts < 20);
        return ['error' => 'Ошибка получения информации о пользователе (' . ($result['error']['error_msg'] ?? $result['error'] ?? 'unknow') . ')'];
    }

}