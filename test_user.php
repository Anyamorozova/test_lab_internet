<?php

class User
{
    public $login;
    public $first_name;
    public $last_name;
    public $phone;
    public $email;

    public function __construct()
	{
        $dbh = new sdbh(); //подключение к базе 
    }

    public function authorization($login,$password) // авторизация пользователя
    {
        $result_row = $dbh->query("SELECT id FROM users WHERE login = '$login'");
        if ($result_row->num_rows == 1)
        {
            $array_result = $result_row->fetch_assoc();
            $password_db = $array_result["password"];
            if ($password == self::decode($password_db,$login)) 
            {
                $this->login = $login;
                return true; 
            }
            else return false;
        }
        else return false;
    }

    public function addUser($login,$password) // добавление пользователя (запись логина и пароля)
    {
        // проверка, зарегистрирован пользователь или нет в базе 
        $result_row = $dbh->query("SELECT id FROM users WHERE login = '$login'");
        
        if ($result_row->num_rows == 0) // если записи нет, то добавляем в БД 
        {
            // чтобы не хранить пароли в открытом виде, зашифруем пароль по логину
            $password = self::encode($password,$login);
            $result_row = $dbh->query("INSERT INTO users (login,password) VALUES ('$login','$password')");
        }
        $this->login = $login;
        return true;
    }

    public function updateUser($update) // обновление данных у пользователя, в массиве update поля, которые хочет обновить пользователь
    {
        if (empty($update)) return true; // если передали пустой массив, то сразу завершаем выполнение

        $login_first = $this->login;
        $result_row = $dbh->query("SELECT id, password FROM users WHERE login = '$login_first'");
        if ($result_row->num_rows == 1)
        {
            $str_set = "";
            // т.к. логин и пароль связаны, отдельный разбор 
            if (array_key_exists('login', $update) && array_key_exists('password', $update)) // если переданы и логин, и пароль, то переданный пароль зашифровать новым логином
            {
                $this->login = $update["login"];
                $password = self::encode($update["password"],$this->login);
                $str_set .= "password = '$password', login = '$this->login',";

            }
            elseif (array_key_exists('login', $update) && !array_key_exists('password', $update)) // если перадан только логин, нужно изменить зашифрованный пароль
            {
                
                $array_result = $result_row->fetch_assoc();
                $password_db = $array_result["password"]; // получение зашифрованного пароля
                $password_db == self::decode($password_db,$this->login); // получение оригинального пароля, по текущему логину
                $this->login = $update["login"]; // обновление ссылки логина на новый
                $password = self::encode($password_db,$this->login); //шифрование пароля по новому логину
                
                $str_set .= "password = '$password', login = '$this->login',";
            }
            elseif (!array_key_exists('login', $update) && array_key_exists('password', $update)) // если перадан пароль, то зашифровать переданный пароль 
            {
                $password = self::encode($update["password"],$this->login);
                $str_set .= "password = '$password',";
            }

            foreach ($update as $item => $value) 
            {
                if ($item != "password" || $item != "login") $str_set .= "$item = '$value',"; // если данные не пароль и не логин, добавляем в изменение

            }
            
            $str_set = substr($str_set, 0, -1); // удаление последней запятой
            $result_row = $dbh->query("UPDATE users SET $str_set WHERE login = '$login_first';");
            return true;
        }
        else return false;
    }

    public function getInfoByUser() // получить информацию о пользователе (в виде массива)
    {
        $result_row = $dbh->query("SELECT * FROM users WHERE login = '$this->login'");
        if ($result_row->num_rows == 1)
        {
            $array_result = $result_row->fetch_assoc();
            foreach ($array_result as $item => $value) 
            {
                if ($item == "first_name") $this->first_name = $value;
                elseif ($item == "last_name") $this->last_name = $value;
                elseif ($item == "phone") $this->phone = $value;
                elseif ($item == "email") $this->email = $value;

            }
            return $array_result;
        }
        else return false;
    }

    public function delUser() // удалить пользователя
    {
        $result_row = $dbh->query("SELECT id FROM users WHERE login = '$this->login'");
        if ($result_row->num_rows == 1)
        {
            $result_row = $dbh->query("DELETE FROM users WHERE login = '$this->login';"); 
            $this->login = null; 
            $this->first_name = null;
            $this->last_name = null;
            $this->phone = null;
            $this->email = null;

            return true;
        }
        else 
        {
            $this->login = null; 
            return true;
        }
    }

    private function encode($unencoded,$key) //Шифрование по ключу
    { 
		$string = base64_encode($unencoded);
		$newstr = "";
		$arr = array();
		$x = 0;
		while ($x++ < strlen($string)) 
        {
		    $arr[$x-1] = md5(md5($key.$string[$x-1]).$key);
		    $newstr = $newstr.$arr[$x-1][3].$arr[$x-1][6].$arr[$x-1][1].$arr[$x-1][2];
		}
		return $newstr;
	}
		
	private function decode($encoded, $key) //обратное расшифрование
    { 
		$strofsym = "qwertyuiopasdfghjklzxcvbnm1234567890QWERTYUIOPASDFGHJKLZXCVBNM=";//Символы, с которых состоит base64-ключ
		$x = 0;
		while ($x++ <= strlen($strofsym)-1) 
        {
			$tmp = md5(md5($key.$strofsym[$x-1]).$key);//Хеш, который соответствует символу, на который его заменят.
			$encoded = str_replace($tmp[3].$tmp[6].$tmp[1].$tmp[2], $strofsym[$x-1], $encoded);//Заменяем №3,6,1,2 из хеша на символ
		}
		return base64_decode($encoded);// вывод расшифрованной строки
	}
}
