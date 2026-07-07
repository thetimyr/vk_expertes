<?php
// ================= КОНФИГУРАЦИЯ =================
// Вставьте ваш Сервисный ключ доступа ВК (Service Token)
$access_token = 'ВАШ_СЕРВИСНЫЙ_ТОКЕН_ВКОНТАКТЕ';

// ID пользователя (например, https://vk.com/id1 -> 1)
$user_id = '1'; 

// Эти данные не отдаются официальным API ВК, но нужны для макета. 
// Замените их на свои при необходимости.
$manual_rating = '3,4';
$manual_theme = 'Стиль';
// ================================================

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Функция для запросов к API VK
function vkRequest($method, $params = []) {
    global $access_token;
    $params['access_token'] = $access_token;
    $params['v'] = '5.131';
    
    $url = 'https://api.vk.com/method/' . $method . '?' . http_build_query($params);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'VK-Experts-Mock/1.0');
    $response = curl_exec($ch);
    curl_close($ch);
    
    return json_decode($response, true);
}

// Засекаем время для расчета задержки
$start_time = microtime(true);

// 1. Получаем данные пользователя (Имя, Фамилия, Аватар)
$user_data = vkRequest('users.get', ['user_ids' => $user_id, 'fields' => 'photo_200_orig']);
if (isset($user_data['response'][0])) {
    $full_name = $user_data['response'][0]['first_name'] . ' ' . $user_data['response'][0]['last_name'];
    $avatar_url = $user_data['response'][0]['photo_200_orig'];
} else {
    $full_name = 'Неизвестный Пользователь';
    $avatar_url = 'https://vk.com/images/camera_200.png'; // Дефолтная ава
}

// 2. Получаем общее количество записей (стен)
$wall_total = vkRequest('wall.get', ['owner_id' => $user_id, 'count' => 0]);
$total_posts = isset($wall_total['response']['count']) ? $wall_total['response']['count'] : 0;

// 3. Получаем записи за сегодня
$today_posts = 0;
$today_likes = 0;
$wall_today = vkRequest('wall.get', ['owner_id' => $user_id, 'filter' => 'owner', 'count' => 100]); // Проверяем последние 100

$today_date = date('Y-m-d');
if (isset($wall_today['response']['items'])) {
    foreach ($wall_today['response']['items'] as $item) {
        if (date('Y-m-d', $item['date']) === $today_date) {
            $today_posts++;
            // Суммируем лайки, если они есть
            if (isset($item['likes']['count'])) {
                $today_likes += $item['likes']['count'];
            }
        }
    }
}

// 4. Подсчет задержки API и статуса серверов
$api_delay = round((microtime(true) - $start_time) * 1000, 0);
if ($api_delay < 50) {
    $server_status = 'отличное';
} elseif ($api_delay < 200) {
    $server_status = 'хорошее';
} elseif ($api_delay < 500) {
    $server_status = 'среднее';
} else {
    $server_status = 'плохое';
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VK Experts</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        }
        body {
            background: #2b2b2b;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .app-container {
            width: 100%;
            max-width: 360px;
            background: #5d5d5d;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
            border-radius: 2px;
            overflow: hidden;
        }
        /* Header */
        .header {
            background: #424242;
            padding: 12px 15px;
            display: flex;
            align-items: center;
            border-bottom: 1px solid #353535;
        }
        .header-icon {
            display: inline-block;
            margin-right: 12px;
        }
        .header-icon svg {
            width: 28px;
            height: 28px;
            fill: #000000;
        }
        .header-title {
            font-size: 20px;
            color: #ffffff;
            font-weight: 400;
            letter-spacing: 0.5px;
        }
        
        /* Profile Card */
        .profile-section {
            padding: 15px 20px 10px 20px;
            text-align: center;
        }
        .avatar-wrapper {
            position: relative;
            width: 110px;
            height: 110px;
            margin: 5px auto 10px auto;
        }
        .avatar {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #8a8a8a;
        }
        /* Визуальный эффект красных лазеров (опционально, если аватарка без них) */
        .avatar-wrapper::after {
            content: "";
            position: absolute;
            top: 45%;
            left: 25%;
            width: 50%;
            height: 25%;
            background: radial-gradient(circle, rgba(255,0,0,0.4) 0%, rgba(255,0,0,0) 80%);
            filter: blur(8px);
            pointer-events: none;
        }

        .user-name {
            font-size: 20px;
            color: #ffffff;
            margin-bottom: 8px;
            word-wrap: break-word;
        }
        .user-stats {
            color: #e6e6e6;
            font-size: 15px;
            line-height: 1.6;
        }
        
        /* Action Buttons */
        .action-block {
            margin-top: 15px;
        }
        .action-btn {
            display: block;
            width: 100%;
            padding: 15px 0;
            background: #6a6a6a;
            color: #ffffff;
            text-decoration: none;
            text-align: center;
            font-size: 16px;
            border-top: 1px solid #5b5b5b;
            border-bottom: 1px solid #7c7c7c;
            transition: background 0.2s;
        }
        .action-btn:active {
            background: #585858;
        }

        /* Footer Status */
        .footer-section {
            padding: 20px 15px;
            text-align: center;
            background: #6a6a6a;
            color: #d1d1d1;
            font-size: 14px;
            line-height: 1.6;
        }
        .status-label {
            color: #ffffff;
            font-weight: 500;
        }
    </style>
</head>
<body>

<div class="app-container">
    <!-- Header -->
    <div class="header">
        <div class="header-icon">
            <!-- SVG иконка восьмерки/узла -->
            <svg viewBox="0 0 24 24">
                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm0-14.5c-1.38 0-2.5 1.12-2.5 2.5s1.12 2.5 2.5 2.5 2.5-1.12 2.5-2.5-1.12-2.5-2.5-2.5zm0 5.5c-2.76 0-5 2.24-5 5h10c0-2.76-2.24-5-5-5z"/>
            </svg>
        </div>
        <div class="header-title">VK Experts</div>
    </div>

    <!-- Profile Block -->
    <div class="profile-section">
        <div class="avatar-wrapper">
            <img src="<?php echo $avatar_url; ?>" alt="Avatar" class="avatar">
        </div>
        
        <div class="user-name"><?php echo $full_name; ?></div>
        
        <div class="user-stats">
            Рейтинг: <?php echo $manual_rating; ?><br>
            Лента: <?php echo $manual_theme; ?><br>
            Записей за всё время: <?php echo $total_posts; ?>.<br>
            Записей за сегодня: <?php echo $today_posts; ?>.
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="action-block">
        <a href="#" class="action-btn">Рейтинг</a>
        <a href="#" class="action-btn">Полезные ссылки</a>
    </div>

    <!-- Footer Status -->
    <div class="footer-section">
        Статус серверов ВК:<span class="status-label"><?php echo $server_status; ?></span><br>
        Задержка VKAPI:<span class="status-label"><?php echo $api_delay; ?>ms</span>
    </div>
</div>

</body>
</html>