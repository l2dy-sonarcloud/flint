<?php

class Nano_Db
{
    private static $_db;

    public static function init()
    {
        if (!is_object(self::$_db)) {
            if ($config = @parse_ini_file($_SERVER['DOCUMENT_ROOT'] . '/../config/sqlite.cnf')) {
                try {
                    self::$_db = new PDO('sqlite:' . $_SERVER['DOCUMENT_ROOT'] . '/../db/' . $config['database']);
                }
                catch (PDOException $e) {
                    echo "DB Connect Error: {$e->getMessage()}";
                    die();
                }
            }
        }
    }

    public static function setDb($db)
    {
        self::$_db = new PDO($db);
    }

    public static function unsetDb()
    {
        self::$_db = null;
        self::init();
    }

    public static function execute($sql, $bind = array())
    {
        $res = self::$_db->prepare($sql);

	if ($res === false) {
            error_log("Unable to prepare (for execution): {$sql}: " . implode(self::$_db->errorInfo()));
            return false;
	}

        if ($res->execute($bind)) {
            return true;
        }

        return false;
    }

    public static function query($sql, $bind = array())
    {
        $res = self::$_db->prepare($sql);

	if ($res === false) {
            error_log("Unable to prepare (for query): {$sql}: " . implode(self::$_db->errorInfo()));
            return false;
	}

        if ($res->execute($bind)) {
            if ($result = $res->fetchAll(PDO::FETCH_ASSOC)) {
                return $result;
            }
        }

        return false;
    }
}

Nano_Db::init();
