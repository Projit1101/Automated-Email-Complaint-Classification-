<?php

class Database {

    private static $host = "localhost";
    private static $user = "root";
    private static $pass = "";
    private static $db   = "cesc_db";

    public static function connect() {

        $cn = mysqli_connect(
            self::$host,
            self::$user,
            self::$pass,
            self::$db
        );

        if (!$cn) {
            die("Database Connection Failed: " . mysqli_connect_error());
        }

        return $cn;
    }
}