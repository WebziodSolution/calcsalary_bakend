<?php
namespace Common\Services;

use PDO;
use Exception;

class DbHelper {
    private static $dbInstance = null;

    /**
     * Get active database connection.
     */
    public static function getDb() {
        if (self::$dbInstance === null) {
            global $db;
            if (!isset($db) || !($db instanceof PDO)) {
                // Try fallback config loading if db is not global
                $config = require __DIR__ . '/../../config/settings.php';
                $dsn = "mysql:host={$config['db_host']};port={$config['db_port']};dbname={$config['db_name']};charset=utf8mb4";
                $options = [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ];
                self::$dbInstance = new PDO($dsn, $config['db_user'], $config['db_pass'], $options);
            } else {
                self::$dbInstance = $db;
            }
        }
        return self::$dbInstance;
    }

    /**
     * Map a database row (associative array) to a model object instance.
     */
    public static function mapToModel($row, $modelClass) {
        if (!$row) return null;
        $model = new $modelClass();
        $fieldsMap = $modelClass::$fieldsMap;
        foreach ($fieldsMap as $prop => $col) {
            if (array_key_exists($col, $row)) {
                $model->$prop = $row[$col];
            }
        }
        return $model;
    }

    /**
     * Extract database primary key column name and model property name.
     */
    public static function getPrimaryKeyInfo($modelClass) {
        $fieldsMap = $modelClass::$fieldsMap;
        // Typically, primary keys are 'id', 'roleActionId', 'employeeId', 'roleId', 'actionId', 'themeId', 'shiftId', 'moduleId'
        foreach ($fieldsMap as $prop => $col) {
            $lowerProp = strtolower($prop);
            if ($lowerProp === 'id' || $lowerProp === 'roleactionid' || $lowerProp === 'employeeid' || $lowerProp === 'roleid' 
                || $lowerProp === 'actionid' || $lowerProp === 'themeid' || $lowerProp === 'shiftid' 
                || $lowerProp === 'moduleid') {
                return ['prop' => $prop, 'col' => $col];
            }
        }
        // Fallback: match any property ending with 'Id' case-insensitively
        foreach ($fieldsMap as $prop => $col) {
            if (preg_match('/id$/i', $prop)) {
                return ['prop' => $prop, 'col' => $col];
            }
        }
        return ['prop' => 'id', 'col' => 'id'];
    }

    /**
     * Helper to extract ID from dynamic values (which could be numeric, object, or array).
     */
    public static function extractIdValue($value) {
        if (is_object($value)) {
            if (property_exists($value, 'id') && $value->id !== null) return $value->id;
            if (property_exists($value, 'employeeId') && $value->employeeId !== null) return $value->employeeId;
            if (property_exists($value, 'roleId') && $value->roleId !== null) return $value->roleId;
            if (property_exists($value, 'actionId') && $value->actionId !== null) return $value->actionId;
            if (property_exists($value, 'themeId') && $value->themeId !== null) return $value->themeId;
            if (property_exists($value, 'shiftId') && $value->shiftId !== null) return $value->shiftId;
            if (property_exists($value, 'moduleId') && $value->moduleId !== null) return $value->moduleId;
        } elseif (is_array($value)) {
            if (isset($value['id'])) return $value['id'];
            if (isset($value['employeeId'])) return $value['employeeId'];
            if (isset($value['roleId'])) return $value['roleId'];
            if (isset($value['actionId'])) return $value['actionId'];
        }
        return $value;
    }

    /**
     * Find all records from a table mapped to model class.
     */
    public static function findAll($modelClass, $whereSql = "", $params = [], $orderBy = "", $limit = "") {
        $db = self::getDb();
        $tableName = $modelClass::$tableName;
        $sql = "SELECT * FROM `$tableName`";
        if (!empty($whereSql)) {
            $sql .= " WHERE " . $whereSql;
        }
        if (!empty($orderBy)) {
            $sql .= " ORDER BY " . $orderBy;
        }
        if (!empty($limit)) {
            $sql .= " LIMIT " . $limit;
        }
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $results = [];
        foreach ($rows as $row) {
            $results[] = self::mapToModel($row, $modelClass);
        }
        return $results;
    }

    /**
     * Find single record by ID.
     */
    public static function findById($modelClass, $id) {
        if ($id === null) return null;
        $db = self::getDb();
        $tableName = $modelClass::$tableName;
        $pkInfo = self::getPrimaryKeyInfo($modelClass);
        $pkCol = $pkInfo['col'];
        $stmt = $db->prepare("SELECT * FROM `$tableName` WHERE `$pkCol` = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? self::mapToModel($row, $modelClass) : null;
    }

    /**
     * Find first record matching where statement.
     */
    public static function findFirst($modelClass, $whereSql, $params = [], $orderBy = "") {
        $results = self::findAll($modelClass, $whereSql, $params, $orderBy, "1");
        return count($results) > 0 ? $results[0] : null;
    }

    /**
     * Insert a model object into database.
     */
    public static function insert($model) {
        $db = self::getDb();
        $modelClass = get_class($model);
        $tableName = $modelClass::$tableName;
        $fieldsMap = $modelClass::$fieldsMap;
        $pkInfo = self::getPrimaryKeyInfo($modelClass);
        $pkProp = $pkInfo['prop'];

        $cols = [];
        $placeholders = [];
        $params = [];
        foreach ($fieldsMap as $prop => $col) {
            // Exclude auto-increment primary key if it is null
            if ($prop === $pkProp && $model->$prop === null) {
                continue;
            }
            $cols[] = "`$col`";
            $placeholders[] = ":$prop";
            
            // Extract relation ID if it's an object
            $val = self::extractIdValue($model->$prop);
            
            if ($val instanceof \DateTimeInterface) {
                $val = $val->format('Y-m-d H:i:s');
            }
            
            // Convert boolean to 1 or 0
            if (is_bool($val)) {
                $val = $val ? 1 : 0;
            }
            $params[$prop] = $val;
        }

        $sql = "INSERT INTO `$tableName` (" . implode(', ', $cols) . ") VALUES (" . implode(', ', $placeholders) . ")";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        if ($pkProp && $model->$pkProp === null) {
            $model->$pkProp = (int)$db->lastInsertId();
        }
        return $model;
    }

    /**
     * Update a model object in database.
     */
    public static function update($model) {
        $db = self::getDb();
        $modelClass = get_class($model);
        $tableName = $modelClass::$tableName;
        $fieldsMap = $modelClass::$fieldsMap;
        $pkInfo = self::getPrimaryKeyInfo($modelClass);
        $pkProp = $pkInfo['prop'];
        $pkCol = $pkInfo['col'];

        if ($model->$pkProp === null) {
            throw new Exception("Cannot update model without primary key value");
        }

        $sets = [];
        $params = [];
        foreach ($fieldsMap as $prop => $col) {
            if ($prop === $pkProp) {
                continue;
            }
            $sets[] = "`$col` = :$prop";
            
            $val = self::extractIdValue($model->$prop);
            if ($val instanceof \DateTimeInterface) {
                $val = $val->format('Y-m-d H:i:s');
            }
            if (is_bool($val)) {
                $val = $val ? 1 : 0;
            }
            $params[$prop] = $val;
        }
        $params['pk_val'] = $model->$pkProp;

        $sql = "UPDATE `$tableName` SET " . implode(', ', $sets) . " WHERE `$pkCol` = :pk_val";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $model;
    }

    /**
     * Delete a model record by ID.
     */
    public static function delete($modelClass, $id) {
        $db = self::getDb();
        $tableName = $modelClass::$tableName;
        $pkInfo = self::getPrimaryKeyInfo($modelClass);
        $pkCol = $pkInfo['col'];
        $stmt = $db->prepare("DELETE FROM `$tableName` WHERE `$pkCol` = :id");
        return $stmt->execute(['id' => $id]);
    }
}
