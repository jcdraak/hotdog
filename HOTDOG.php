<?php

// Настройки
$ini = (object) parse_ini_file('setting.ini');

// Значения проверок по дефолту
$ERROR_OVERHOT = false; // Перегрев
$ERROR_MINLOAD = false; // Простой

// Получаю данные с датчиков видеокарт
$data = getGpuData();

// TOP_MARGIN
echo str_repeat("\n", $ini->TOP_MARGIN);

// HEADER
writeln("ОПРОС ВИДЕОКАРТ СИСТЕМЫ");
$splt = '+-------+----------------------+-------+-------------+-------+-------+-------+';
writeln($splt);
writeln('| Index | Name                 | Temp  |    Clock    | Power | Load  | Fan   |');
writeln($splt);

// LINES
foreach ( $data as $item ){

  $watt_label = ( $item->poser_watt == 'No') ? " " : $item->poser_watt . ' W';

  writeln("|     {$item->index} | "
      . str_pad($item->name, 20) . " | "
      . str_pad($item->temperature . ' °C', 5) . " | "
      . str_pad($item->clocks_mhz, 11, ' ', STR_PAD_BOTH) . " | "
      . str_pad($watt_label, 5) . " | "
      . str_pad($item->utilization_gpu . " %", 5) . " | "
      . str_pad($item->fan_speed, 5) . " | "
  );


  // CHECK
  if ( $item->temperature     >= $ini->MAX_TEMP ) $ERROR_OVERHOT = $item;
  if ( $ini->MIN_CORE != "-1" and $item->utilization_gpu <= $ini->MIN_CORE ) $ERROR_MINLOAD = $item;

}

// ПОЛЬЗОВАТЕЛЬСКИЕ УСТАНОВКИ
$lbl_mincore = ( $ini->MIN_CORE == "-1" ) ? "NO USE" : $ini->MIN_CORE." %";
writeln($splt);
writeln();
writeln("ПОЛЬЗОВАТЕЛЬСКИЕ УСТАНОВКИ");
writeln('+----------------------------------------------------------------------------+');
writeln("| Максимально допустимая температура: " . str_pad($ini->MAX_TEMP." °C", 40) . "|");
writeln("| " . str_pad("Минимальное значение загрузки ядра: {$lbl_mincore}",     106 )  . "|");
writeln('+----------------------------------------------------------------------------+');


// РЕЗУЛЬТАТ ПРОВЕРКИ ЗНАЧЕНИЙ
writeln();
writeln("РЕЗУЛЬТАТ ПРОВЕРКИ ЗНАЧЕНИЙ");
writeln('+----------------------------------------------------------------------------+');
if ( !$ERROR_OVERHOT and !$ERROR_MINLOAD ) {
  writeln("| Проблем не обнаружено, все значения в допустимых пределах.                 |");
  writeln('+----------------------------------------------------------------------------+');
}
if ( $ERROR_OVERHOT ) {
  writeln("| ВНИМАНИЕ! Привышение температуры видеокарты, максимум установлен {$ini->MAX_TEMP} °C     |");
  writeln("|           {$ERROR_OVERHOT->index}, "
      . str_pad($ERROR_OVERHOT->name . " / Clock: " . $ERROR_OVERHOT->clocks_mhz . " / Temp: " . $ERROR_OVERHOT->temperature . " °C / Load: {$ERROR_OVERHOT->utilization_gpu} %", 63)
      . "|");
  writeln('+----------------------------------------------------------------------------+');
}
if ( $ERROR_MINLOAD ) {
  writeln("| ВНИМАНИЕ! Минимальная загрузка ядра видеокарты, минимум установлен {$ini->MIN_CORE} %    |");
  writeln("|           {$ERROR_MINLOAD->index}, "
      . str_pad($ERROR_MINLOAD->name . " / Clock: " . $ERROR_MINLOAD->clocks_mhz . " / Temp: " . $ERROR_MINLOAD->temperature . " °C / Load: {$ERROR_MINLOAD->utilization_gpu} %", 63)
      . "|");
  writeln('+----------------------------------------------------------------------------+');
}

// БОТ СЛУШАЕТ КОМАНДЫ
if ( $ini->LISTEN_ORDER == '1' ){
  writeln();
  writeln("ПРОВЕРКА КОМАНД В ЛИЧКЕ В ТЕЛЕГРАМЕ");
  waitingYourOrders();
}


// ДЕЙСТВИЯ ПО РЕЗУЛЬТАТАМ ПРОВЕРКИ
writeln();
writeln("ДЕЙСТВИЯ ПО РЕЗУЛЬТАТАМ ПРОВЕРКИ");
writeln('+----------------------------------------------------------------------------+');
if ( !$ERROR_OVERHOT and !$ERROR_MINLOAD ) {
  writeln("| Действий не требуется.                                                     |");
  writeln('+----------------------------------------------------------------------------+');
}
if ( $ERROR_OVERHOT ) {
  writeln("| 1. Делаем снимок данного окна и отправляем в телеграм                      |");
  writeln("| 2. Выключаем компьютер в экстренном режиме                                 |");
  writeln('+----------------------------------------------------------------------------+');
  sendScreen();
  shutDown();
}
if ( $ERROR_MINLOAD and !$ERROR_OVERHOT ) {
  writeln("| 1. Делаем снимок данного окна и отправляем в телеграм                      |");
  writeln("| 2. Перезагружаем компьютер                                                 |");
  writeln('+----------------------------------------------------------------------------+');
  sendScreen();
  reBoot();
}

writeln();
writeln("ЖДЕМ {$ini->CHECK_INTERVAL} секунд");
writeln(exec('echo %TIME%'));
sleep( $ini->CHECK_INTERVAL);




function reBoot(){
  exec('restart_system.bat');
  sleep(60);
}
function shutDown(){
  exec('shutdown_system.bat');
  sleep(60);
}
function getGpuData(){

  exec('"C:\Program Files\NVIDIA Corporation\NVSMI\nvidia-smi" --query-gpu=index,name,clocks.gr,power.draw,utilization.gpu,fan.speed,temperature.gpu --format=csv', $res);

  $headers = array(
      '0' => 'index',
      '1' => 'name',
      '2' => 'clocks_mhz',
      '3' => 'poser_watt',
      '4' => 'utilization_gpu',
      '5' => 'fan_speed',
      '6' => 'temperature',
  );
  $data = array();
  foreach ( $res as $key => $item){
    if ( $key != 0 ) {
      $rows = explode(',', $item);

      $rows[0] = trim($rows[0]);
      $rows[1] = trim($rows[1]);
      $rows[2] = trim($rows[2]);
      $rows[3] = trim($rows[3]);
      $rows[4] = trim($rows[4]);
      $rows[5] = trim($rows[5]);
      $rows[6] = trim($rows[6]);

      if ( $rows[3] == "[Not Supported]") $rows[3] = 'No'; else $rows[3] = ceil($rows[3]);
      if ( $rows[5] == "[Not Supported]") $rows[5] = 'No';
      $rows[4] = trim(str_replace('%','',$rows[4]));

      $data[] = ( object ) array(
          ''.$headers[0] => $rows[0],
          ''.$headers[1] => $rows[1],
          ''.$headers[2] => $rows[2],
          ''.$headers[3] => $rows[3],
          ''.$headers[4] => $rows[4],
          ''.$headers[5] => $rows[5],
          ''.$headers[6] => $rows[6],
      );
    }
  }
  return $data;
}
function writeln( $s = '' ){
  GLOBAL $ini;
  echo str_repeat(' ', $ini->LEFT_MARGHIN) . $s."\n";
}
function sendScreen( $chat_id = 0){
  GLOBAL $ini;
  exec('screenCapture.bat temp.jpg "HOTDOG" > nul');
  sleep(3);

  if ( !$chat_id ) $chat_id = $ini->TELEGRAM_CHATID;

  exec('curl -s -X POST "https://api.telegram.org/bot'.$ini->TELEGRAM_TOKEN.'/sendPhoto?chat_id='.$chat_id.'" -F photo="@temp.jpg" -H "Content-Type:multipart/form-data" > nul');
}
function waitingYourOrders(){

  writeln('+----------------------------------------------------------------------------+');




  GLOBAL $ini;

  // LISTEN BOT :: LAST ID сообщения на которое ответили
  $fn =  __DIR__ . DIRECTORY_SEPARATOR . 'telegram_bot.txt';
  $message_min = 0; if ( file_exists( $fn )) $message_min = intval(file_get_contents($fn)); $message_min = ($message_min) ? $message_min : 0;

  //exec('curl https://api.telegram.org/bot'.$ini->TELEGRAM_TOKEN.'/getUpdates > telegram_history.txt', $json);
  exec('curl --silent https://api.telegram.org/bot'.$ini->TELEGRAM_TOKEN.'/getUpdates', $json);
  $json = implode('',$json);
//  $json = file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'telegram_history.txt');
  $data = json_decode($json);

  // LISTEN BOT :: LOOKING LINES
  $chat_message_id = 0; $nomess = true;
  if ( is_object($data) and is_array($data->result) ) {
    foreach( $data->result as $item){
      $chat_type       = $item->message->chat->type;
      $chat_id       = $item->message->chat->id;
      $chat_text       = $item->message->text;
      $chat_message_id = $item->message->message_id;
      if ( $chat_type == 'private' and $chat_message_id > $message_min and stristr($chat_text, 'покажи стату')){
        sendScreen( $chat_id );
        $nomess = false;
        writeln("| Найдена команда: {$chat_text} "  );
        // https://api.telegram.org/bot<Bot_token>/sendMessage?chat_id=<chat_id>&text=Привет%20мир
/*
        // TELEGRAMM
        $params = array(
            'chat_id'=>$chat_id,
            'reply_to_message_id'=>$chat_message_id,
            'text'=>"Тут типа будет стата",
        );
        $ch = curl_init('https://api.telegram.org/bot'.$ini->TELEGRAM_TOKEN.'/sendMessage');
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, ($params));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_exec($ch);
        curl_close($ch);
        */
      }

    }
  }

  // LISTEN BOT :: UPDATE LAST ID
  if ( file_exists( $fn )) unlink( $fn );
  $fp = fopen($fn, 'w'); fwrite($fp, $chat_message_id); fclose($fp);
  if ( $nomess ) writeln("| Нам никто не писал больше, ничего не просил сделать хы                     |");
  writeln('+----------------------------------------------------------------------------+');
}
