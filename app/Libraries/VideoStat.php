<?php

namespace App\Libraries;

use Config\Database;
use CodeIgniter\Database\Exceptions\DatabaseException;

/*
 * Статистика просмотра реклам
 */

class VideoStat
{

    /*
     * Имя таблицы для записи в статистику
     */
    public static string $tableName = 'video_stat_daily';

    /*
     * Информация о пользователе, необходимая для статистики
     */
    public static array $userFields = ['id', 'payment_count', 'magazine_count', 'rev', 'platform', 'mobile', 'ad_id', 'create', 'map', 'point'];

    /*
     * Данные для вставки
     */
    public static array $data = [];

    /*
     * Подготовка данных для вставки
     */
    private static function prepareInsertData($source, $userInfo): array
    {
        self::$data = [
            'time' => date('Y-m-d H:i:s'),
            'type' => 'interstetial',
            'source' => $source
        ];
        foreach (self::$userFields as $field) {
            if (isset($userInfo[$field])) {
                self::$data[$field] = $userInfo[$field];
            }
        }
        return self::$data;
    }

    /*
     * Сохранение данных по статистики просмотров
     */
    public static function saveData($source, $userInfo): void
    {
        self::$data = self::prepareInsertData($source, $userInfo);
        $db = Database::connect();
        try {
            $db->table(self::$tableName)->insert(self::$data);
        } catch (DatabaseException $e) {
            App::sendResponseError('Ошибка записи данных в video_stat_daily: ' . $e->getMessage(), 'video_stat_error', 200);
        } finally {
            $db->close();
        }
    }

    /*
     * Подготовка данных для дополнительного запроса в транзакции пользователя
     */
    public static function prepareDataToInsert($source, $userInfo): array
    {
        return [
            'table_name' => self::$tableName,
            'data' => self::prepareInsertData($source, $userInfo),
            'action' => 'insert'
        ];
    }

}