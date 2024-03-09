<?php

namespace App\Libraries;

use Config\Services;
use Exception;

/**
 * Класс для работы с социальной сетью ВК
 */
class Vk
{
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
            return ['error' => 'Неверная подпись запроса'.(isset($params['viewer_id']) ? ', viewer_id: '.$params['viewer_id'] : '' )];
        }
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
            return ['error' => 'Ошибка отправки запроса ('.$e->getMessage().')'];
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
            $result = $this->sendRequest( 'users.get', $query);
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
		return [ 'error' => 'Ошибка получения информации о пользователе ('.($result['error']['error_msg'] ?? $result['error'] ?? 'unknow').')' ];
    }

}