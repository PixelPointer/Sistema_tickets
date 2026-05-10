<?php
date_default_timezone_set('America/La_Paz');

class Database {
    private static $host = "localhost";
    private static $db_name = "sistema_tickets";
    private static $username = "root"; 
    private static $password = "";     
    private static $conn = null;

    public static function getConnection() {
        if (self::$conn === null) {
            try {
                self::$conn = new PDO("mysql:host=" . self::$host . ";dbname=" . self::$db_name, self::$username, self::$password);
                self::$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                self::$conn->exec("set names utf8");
            } catch(PDOException $exception) {
                die("Error de conexión a la base de datos: " . $exception->getMessage());
            }
        }
        return self::$conn;
    }
}
