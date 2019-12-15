<?php

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

require __DIR__ . '/vendor/autoload.php';

use Twig\Environment;
use Twig\Loader\FilesystemLoader;

DEFINE('HOST', '127.0.0.1');
DEFINE('USER', 'root');
DEFINE('PASSWORD', 'a');
DEFINE('DB_NAME', 'dling');
DEFINE('APP_NAME', 'آذین ویستا');
DEFINE('APP_SITE', 'azinvista.com');
DEFINE('ELASTIC_HOST', 'https://6b9o9l9h74:yqegnzdccv@first-cluster-6104576857.us-east-1.bonsaisearch.net:443');
DEFINE('ZARRIN', '2fca4aec-080f-11e8-9daa-000c295eb8fc');


$globalPrices = [
    "daftari" => 15000,
    "passport" => 10000,
    "enteghal" => 10000,
    "shenas_item" => 5000,
    "copy" => 1000,
    "gain" => 5
];


session_start();


$loader = new FilesystemLoader(__DIR__ . '/templates');
$twig = new Environment($loader);
$config = [
    'settings' => [
        'displayErrorDetails' => true,
        'logger' => [
            'name' => 'slim-app',
            'level' => Monolog\Logger::DEBUG,
            'path' => __DIR__ . '/../logs/app.log',
        ],
    ],
];
$app = new \Slim\App($config);


$app->get('/upload_test', function (Request $request, Response $response, $args) use ($twig, $app) {
    \Cloudinary::config(array(
        "cloud_name" => "dr4eclxx1",
        "api_key" => "169382814821652",
        "api_secret" => "Zq7MK4ARDH6lEbuUW2cBok6XB0o",
        "secure" => true
    ));
    $default_upload_options = array('tags' => 'basic_sample');
    $dir = "C:\\Users\\User\\Desktop\\google-images-download-master\\google_images_download\\downloads\\hm3\\";
    $files = scandir($dir);
    foreach ($files as $item) {
        $path = $dir . "" . $item;
        if (!is_dir($path)) {
            echo $path . "<br/>";
            $id = \Cloudinary\Uploader::upload(
                            $path, array_merge(
                                    $default_upload_options, array('public_id' => $item)
                            )
            );
            echo $id . "<br/>";
        }
    }
})->setName('upload_test');

$app->get('/', function (Request $request, Response $response, $args) use ($twig, $app) {
    $conn = getConnection();
    $stmt = $conn->prepare("SELECT * FROM product");
    $stmt->execute();
    $result = $stmt->get_result();
    $list = array();
    while ($row = $result->fetch_assoc()) {
        array_push($list, [ "category" => $row["category"], "url" => $row["url"], "title" => $row["title"]]);
    }
    $stmt->close();
    $conn->close();
    $response->getBody()->write($twig->render('home.twig', [ "user" => getCurrentUser(), "app_name" => APP_NAME, "app_site" => APP_SITE]));
})->setName('home');

$app->get('/main', function (Request $request, Response $response, $args) use ($twig, $app, $globalPrices) {
    $conn = getConnection();
    $stmt = $conn->prepare("SELECT * FROM product");
    $stmt->execute();
    $result = $stmt->get_result();
    $list = array();
    while ($row = $result->fetch_assoc()) {
        array_push($list, [ "category" => $row["category"], "url" => $row["url"], "title" => $row["title"]]);
    }
    $stmt->close();
    $conn->close();
    $response->getBody()->write($twig->render('main.twig', ["products" => $list, "user" => getCurrentUser(), "app_name" => APP_NAME, "app_site" => APP_SITE, "prices" => json_encode($globalPrices)]));
})->setName('main');

$app->get('/register', function (Request $request, Response $response, $args) use ($twig, $app) {
    $referer = urldecode($_SERVER['HTTP_REFERER']);
    if (isLogged()) {
        return $response->withRedirect($app->getContainer()->get('router')->pathFor("main"));
    } else {
        $response->getBody()->write($twig->render('register.twig', ["app_name" => APP_NAME, "app_site" => APP_SITE, "redirect" => $referer]));
    }
})->setName('register');
$app->get('/login', function (Request $request, Response $response, $args) use ($twig, $app) {
    $referer = urldecode($_SERVER['HTTP_REFERER']);
    if (isLogged()) {
        return $response->withRedirect($app->getContainer()->get('router')->pathFor("main"));
    } else {
        $response->getBody()->write($twig->render('login.twig', ["app_name" => APP_NAME, "app_site" => APP_SITE, "redirect" => $referer]));
    }
})->setName('login');

$app->get('/newaddress', function (Request $request, Response $response, $args) use ($twig, $app) {
    $response->getBody()->write($twig->render('newaddress.twig', ["app_name" => APP_NAME, "app_site" => APP_SITE, "user" => getCurrentUser(), "address" => null]));
})->setName('newaddress');

$app->get('/editaddress', function (Request $request, Response $response, $args) use ($twig, $app) {
    if (!isLogged()) {
        return $response->withRedirect($app->getContainer()->get('router')->pathFor("main"));
    } else {
        $id = $request->getQueryParams()["id"];
        $conn = getConnection();
        $stmt = $conn->prepare("SELECT * FROM user_address WHERE user_id=? AND address_id=?");
        $stmt->bind_param("ii", getCurrentUser()["id"], $id);
        $stmt->execute();
        $result = $stmt->get_result();
        if (mysqli_num_rows($result) != 0) {
            $stmt = $conn->prepare("SELECT * FROM address WHERE id=?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $addr = [
                "id" => $id,
                "address" => $row["address"],
                "mobile" => $row["mobile"],
                "point" => $row["point"],
                "name" => $row["name"]
            ];
            $response->getBody()->write($twig->render('newaddress.twig', ["app_name" => APP_NAME, "app_site" => APP_SITE, "user" => getCurrentUser(), "address" => $addr]));
            return;
        }
        $stmt->close();
        $conn->close();
        return $response->withRedirect($app->getContainer()->get('router')->pathFor("main"));
    }
})->setName('ecitaddress');

$app->get('/logout', function (Request $request, Response $response, $args) use ($twig, $app) {
    session_unset();
    session_destroy();
    $referer = urldecode($_SERVER['HTTP_REFERER']);
    return $response->withRedirect($referer);
})->setName('logout');

$app->post('/dologin', function (Request $request, Response $response, $args) use ($twig, $app) {
    $redirect = $_POST["redirect"];
    if (isLogged()) {
        return $response->withRedirect($redirect);
    } else {
        $mail = $_POST["mail"];
        $password = $_POST["password"];

        $conn = getConnection();
        $stmt = $conn->prepare("SELECT * FROM user WHERE mail=? AND password=?");
        $stmt->bind_param("ss", $mail, $password);
        $stmt->execute();
        $result = $stmt->get_result();
        if (mysqli_num_rows($result) != 0) {
            $row = $result->fetch_assoc();
            setCurrentUser($row["name"], $row["id"], $row["mail"], $row["mobile"]);
            $stmt->close();
            $conn->close();
            return $response->withRedirect($redirect);
        } else {
            $stmt->close();
            $conn->close();
            $response->getBody()->write($twig->render('login.twig', ["error" => "رایانامه یا رمز اشتباه است", "app_name" => APP_NAME, "app_site" => APP_SITE]));
        }
    }
})->setName('dologin');

$app->post('/save_user', function (Request $request, Response $response, $args) use ($twig, $app) {
    $redirect = $_POST["redirect"];
    if (isLogged()) {
        return $response->withRedirect($app->getContainer()->get('router')->pathFor("main"));
    } else {
        $name = $_POST["name"];
        $mail = $_POST["mail"];
        $password = $_POST["password"];
        $mobile = $_POST["mobile"];

        $conn = getConnection();
        $stmt = $conn->prepare("SELECT * FROM user WHERE mail=? OR mobile=?");
        $stmt->bind_param("ss", $mail, $mobile);
        $stmt->execute();
        $result = $stmt->get_result();
        if (mysqli_num_rows($result) == 0) {
            $stmt = $conn->prepare("INSERT INTO user(name,mail,mobile,password)VALUES(?,?,?,?)");
            $stmt->bind_param("ssss", $name, $mail, $mobile, $password);
            $stmt->execute();
            $result = $stmt->get_result();
            $last_id = $conn->insert_id;
            setCurrentUser($name, $last_id, $mail, $mobile);
            $stmt->close();
            $conn->close();
            return $response->withRedirect($redirect);
        } else {
            $stmt->close();
            $conn->close();
            $response->getBody()->write($twig->render('register.twig', ["error" => "شماره موبایل یا رایانامه قبلا ثبت شده است", "app_name" => APP_NAME, "app_site" => APP_SITE]));
        }
    }
})->setName('save_user');

$app->post('/save_address', function (Request $request, Response $response, $args) use ($twig, $app) {
    if (!isLogged()) {
        return $response->withRedirect($app->getContainer()->get('router')->pathFor("main"));
    } else {
        $id = (int) ($_POST["id"]);
        $name = $_POST["name"];
        $mobile = $_POST["mobile"];
        $address = $_POST["address"];
        $point = $_POST["point"];
        $conn = getConnection();
        if ($id == -1) {

            $stmt = $conn->prepare("INSERT INTO address(name,mobile,address,point)VALUES(?,?,?,?)");
            $stmt->bind_param("ssss", $name, $mobile, $address, $point);
            $stmt->execute();
            $result = $stmt->get_result();
            $last_id = $conn->insert_id;

            $stmt = $conn->prepare("INSERT INTO user_address(user_id,address_id)VALUES(?,?)");
            $stmt->bind_param("ii", getCurrentUser()["id"], $last_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $stmt->close();
        } else {
            $stmt = $conn->prepare("UPDATE address SET name=?,mobile=?,address=?,point=? WHERE id=?");
            $stmt->bind_param("ssssi", $name, $mobile, $address, $point, $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $stmt->close();
        }
        $conn->close();
        return $response->withRedirect("تعیین_آدرس");
    }
})->setName('save_address');

$app->get('/remove_address', function (Request $request, Response $response, $args) use ($twig, $app) {

    if (!isLogged()) {
        return $response->withRedirect($app->getContainer()->get('router')->pathFor("main"));
    } else {
        $id = $request->getQueryParams()["id"];
        $conn = getConnection();
        $stmt = $conn->prepare("SELECT * FROM user_address WHERE user_id=? AND address_id=?");
        $stmt->bind_param("ii", getCurrentUser()["id"], $id);
        $stmt->execute();
        $result = $stmt->get_result();
        if (mysqli_num_rows($result) != 0) {
            $iid = (int) $id;
            $stmt = $conn->prepare("DELETE FROM address WHERE id=" . $iid);
            $stmt->execute();
        }

        $stmt->close();
        $conn->close();
        $response->getBody()->write(json_encode($id));
        return;
    }
})->setName('remove_address');

$app->get('/profile', function (Request $request, Response $response, $args) use ($twig, $app) {
    if (!isLogged()) {
        return $response->withRedirect($app->getContainer()->get('router')->pathFor("main"));
    } else {
        $response->getBody()->write($twig->render('profile.twig', ["user" => getCurrentUser(), "app_name" => APP_NAME, "app_site" => APP_SITE]));
    }
})->setName('profile');

$app->post('/update_user', function (Request $request, Response $response, $args) use ($twig, $app) {
    if (!isLogged()) {
        return $response->withRedirect($app->getContainer()->get('router')->pathFor("main"));
    } else {
        $name = $_POST["name"];
        $password = $_POST["password"];
        $mobile = $_POST["mobile"];
        $mail = $_POST["mail"];
        $id = getCurrentUser()["id"];

        $conn = getConnection();
        $stmt = $conn->prepare("UPDATE user SET name=?,password=?,mobile=?,mail=? WHERE id=?");
        $stmt->bind_param("sssss", $name, $password, $mobile, $mail, $id);
        $stmt->execute();
        $result = $stmt->get_result();
        setCurrentUser($name, $id, $mail, $mobile);
        $stmt->close();
        $conn->close();
        return $response->withRedirect($app->getContainer()->get('router')->pathFor("main"));
    }
})->setName('update_user');


$app->get('/forget', function (Request $request, Response $response, $args) use ($twig, $app) {
    if (isLogged()) {
        return $response->withRedirect($app->getContainer()->get('router')->pathFor("main"));
    } else {
        $response->getBody()->write($twig->render('forget.twig', ["app_name" => APP_NAME, "app_site" => APP_SITE]));
    }
})->setName('forget');

$app->post('/sendpass', function (Request $request, Response $response, $args) use ($twig, $app) {
    if (isLogged()) {
        return $response->withRedirect($app->getContainer()->get('router')->pathFor("main"));
    } else {
        $mail = $_POST["mail"];
        $conn = getConnection();
        $stmt = $conn->prepare("SELECT * FROM user WHERE mail=?");
        $stmt->bind_param("s", $mail);
        $stmt->execute();
        $result = $stmt->get_result();
        if (mysqli_num_rows($result) == 0) {
            $stmt->close();
            $conn->close();
            $response->getBody()->write($twig->render('forget.twig', ["error" => "چنین ایمیلی در سیستم ثبت نشده است"]));
        } else {
            $row = $result->fetch_assoc();
            $message = "رمز عبور شما: " + $row["password"];
            sendMail($mail, $message, "بازیابی رمز", $twig);
            $stmt->close();
            $conn->close();
            $response->getBody()->write($twig->render('forget.twig', ["success" => true, "app_name" => APP_NAME, "app_site" => APP_SITE]));
        }
    }
})->setName('profile');

$app->get('/act{code}', function (Request $request, Response $response, $args) use ($twig, $app) {
    $code = $args['code'];
    $center = getCenterByLink($code);
    if ($center != null) {
        $user = ["name" => $center->manager];
        setCurrentManager($center);
        $response->getBody()->write($twig->render('mregister.twig', ["app_name" => APP_NAME, "app_site" => APP_SITE, "user" => $user]));
    } else {
        $response->getBody()->write($twig->render('notfound.twig', ["app_name" => APP_NAME, "app_site" => APP_SITE]));
    }
    return;
})->setName('act');

$app->post('/doact', function (Request $request, Response $response, $args) use ($twig, $app) {
    if (!isManagerLogged()) {
        $response->getBody()->write($twig->render('mlogin.twig', ["error" => "", "app_name" => APP_NAME, "app_site" => APP_SITE]));
    } else {
        $mail = $_POST["mail"];
        $account = $_POST["account"];
        $carrier = $_POST["carrier"];
        $duration = $_POST["duration"];
        $license = $_POST["license"];
        $javaz = $_POST["javaz"];
        $password = $_POST["password"];
        $manager = getCurrentManager();
        updateCenter($manager->id, $mail, $account, $carrier, $duration, $license, $javaz, $password);
        return $response->withRedirect($app->getContainer()->get('router')->pathFor("translators"));
    }
})->setName('doact');

$app->get('/translators', function (Request $request, Response $response, $args) use ($twig, $app) {
    if (!isManagerLogged()) {
        return $response->withRedirect($app->getContainer()->get('router')->pathFor("translators-login"));
    }
    $conn = getConnection();
    $stmt = $conn->prepare("SELECT the_order.*,user.name as username,user.mail as mail,user.mobile as user_mobile,address.address as address,address.mobile as address_mobile,address.name as address_name FROM `the_order` LEFT JOIN user ON user_id=user.id lEFT JOIN address ON address_id=address.id WHERE center_id=? AND paycode IS NOT NULL ORDER BY order_time DESC;");
    $stmt->bind_param("i", getCurrentManager()->id);
    $stmt->execute();
    $result = $stmt->get_result();
    $list = array();
    while ($row = $result->fetch_assoc()) {
        array_push($list, [
            "id" => $row["id"],
            "user_id" => $row["user_id"],
            "order_time" => $row["order_time"],
            "status" => $row["status"],
            "address_id" => $row["address_id"],
            "bill" => $row["bill"],
            "amount" => $row["amount"],
            "paycode" => $row["paycode"],
            "username" => $row["username"],
            "mail" => $row["mail"],
            "user_mobile" => $row["user_mobile"],
            "address" => $row["address"],
            "address_mobile" => $row["address_mobile"],
            "address_name" => $row["address_name"],
        ]);
    }
    $stmt->close();
    $conn->close();
    $user = ["name" => getCurrentManager()->manager];
    $response->getBody()->write($twig->render('manage.twig', ["app_name" => APP_NAME, "app_site" => APP_SITE, "user" => $user, "orders" => $list]));
})->setName('translators');
$app->post('/change-state', function (Request $request, Response $response, $args) use ($twig, $app) {
    if (!isManagerLogged()) {
        return $response->withRedirect($app->getContainer()->get('router')->pathFor("translators-login"));
    }
    $params = $request->getQueryParams();
    $orderId = $params["id"];
    $status = $params["status"];
    $managerId = intval(getCurrentManager()->id);

    if ($status == 3) {
        $response->getBody()->write(json_encode(["status" => false, "error" => "last status"]));
        return;
    }
    $status++;

    $conn = getConnection();
    $stmt = $conn->prepare("UPDATE the_order SET status=? WHERE id=? AND center_id=?");
    $stmt->bind_param("iii", $status, $orderId, $managerId);
    $stmt->execute();
    $error = $stmt->error;
    $stmt->close();
    $conn->close();
    $response->getBody()->write(json_encode(["status" => true, "new" => $status]));
    return;
})->setName('translators');

$app->get('/terms', function (Request $request, Response $response, $args) use ($twig, $app, $globalPrices) {
    $response->getBody()->write($twig->render('terms.twig', ["app_name" => APP_NAME, "app_site" => APP_SITE, "gain" => $globalPrices["gain"]]));
})->setName('translators');

$app->get('/translators-login', function (Request $request, Response $response, $args) use ($twig, $app) {
    $referer = urldecode($_SERVER['HTTP_REFERER']);
    $response->getBody()->write($twig->render('mlogin.twig', ["app_name" => APP_NAME, "app_site" => APP_SITE, "user" => getCurrentUser(), "redirect" => $referer]));
})->setName('translators-login');


$app->post('/domlogin', function (Request $request, Response $response, $args) use ($twig, $app) {
    $redirect = $_POST["redirect"];
    if (isManagerLogged()) {
        return $response->withRedirect($app->getContainer()->get('router')->pathFor("translators"));
    } else {
        $mobile = $_POST["mobile"];
        $password = $_POST["password"];
        $center = getCenterByAuth($mobile, $password);
        if ($center != null) {
            setCurrentManager($center);
            return $response->withRedirect($app->getContainer()->get('router')->pathFor("translators"));
        } else {
            $response->getBody()->write($twig->render('mlogin.twig', ["error" => "شماره موبایل یا رمز اشتباه است", "app_name" => APP_NAME, "app_site" => APP_SITE]));
        }
    }
})->setName('domlogin');

$app->get('/centers', function (Request $request, Response $response, $args) use ($twig, $app) {

    if (!isLogged()) {
        return $response->withRedirect($app->getContainer()->get('router')->pathFor("main"));
    } else {
        $params = $request->getQueryParams();
        $point = "35.729357,51.437731";
        if (isset($params["point"])) {
            $point = $params["point"];
        }
        $sort = "loc";
        if (isset($params["sort"])) {
            $sort = $params["sort"];
        }
        $centers = getElasticCenters($point, $sort);
        $response->getBody()->write(json_encode($centers));
        return;
    }
})->setName('centers');

$app->get('/carrier_price', function (Request $request, Response $response, $args) use ($twig, $app) {
    $p1 = $request->getQueryParams()["p1"];
    $p2 = $request->getQueryParams()["p2"];
    $response->getBody()->write(getCarrierPrice($p1, $p2));
    return;
})->setName('centers');

$app->get('/bill', function (Request $request, Response $response, $args) use ($twig, $app, $globalPrices) {
    $response->getBody()->write($twig->render('bill.twig', ["app_name" => APP_NAME, "app_site" => APP_SITE, "user" => getCurrentUser(), "prices" => json_encode($globalPrices)]));
})->setName('bill');

$app->get('/verify', function (Request $request, Response $response, $args) use ($twig, $app, $globalPrices) {
    $order = $_SESSION["order"];
    $auth = $request->getQueryParams()["Authority"];
    $data = array('MerchantID' => ZARRIN, 'Authority' => $auth, 'Amount' => $order["amount"]);
    $jsonData = json_encode($data);
    $ch = curl_init('https://www.zarinpal.com/pg/rest/WebGate/PaymentVerification.json');
    curl_setopt($ch, CURLOPT_USERAGENT, 'ZarinPal Rest Api v1');
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Content-Length: ' . strlen($jsonData)
    ));
    $result = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);
    $result = json_decode($result, true);
    if ($err) {
        $response->getBody()->write($err);
    } else {
        $orderId = $order["id"];
        if ($result['Status'] == 100) {
            $paycode = $result['RefID'];

            $error = verifyOrder($orderId, $paycode);

            $response->getBody()->write($twig->render('receipt.twig', ["app_name" => APP_NAME, "app_site" => APP_SITE, "user" => getCurrentUser(), "order" => $orderId, "paycode" => $paycode, "manager" => $order["center"]->manager, "phone" => $order["center"]->phone]));
            return;
        } else {
            $response->getBody()->write('Transation failed. Status:' . $result['Status']);
            return;
        }
    }
})->setName('verify');

$app->post('/gopay', function (Request $request, Response $response, $args) use ($twig, $app, $globalPrices) {
    $data = $_POST["data"];
    $data = json_decode($data);
    $total = 0;
    $center = getElasticCenter($data->center);
    incCenterScore($data->center);

    if (!isset($center->account)) {
        $response->getBody()->write("شماره حساب دفتر ترجمه ثبت نشده است.");
        return;
    }
    foreach ($data->products as $item) {
        $total+=getProductPrice($item, $globalPrices);
    }
    $total+=$globalPrices["daftari"];
    $carrierPrice = getCarrierPrice($data->addressLoc, $data->centerLoc);
    if ($carrierPrice != -1) {
        $total+=$carrierPrice;
    }
    $total=500;
    $orderId = saveOrder($center->id, $data->address, $_POST["data"], $total);
//    $response->getBody()->write($orderId);
//    return;
    $_SESSION["order"] = ["id" => $orderId, "amount" => $total, "center" => $center];


    $forMe = 5 * $total / 100;
    $forHim = $total - $forMe;

    $item = array();
    $item["Amount"] = $forHim;
    $item["Description"] = "واریز حق ترجمه";
    $additionlData = array();
    $additionlData["zp." . $center->account . ".1"] = $item;
    $payload = array();
    $payload["Wages"] = $additionlData;

    $data = array('MerchantID' => ZARRIN,
        'Amount' => $total,
        'CallbackURL' => 'http://localhost/gling/verify',
        'Description' => 'ثبت سفارش ترجمه',
        'AdditionalData' => json_encode($payload, JSON_UNESCAPED_UNICODE)
    );
    $jsonData = json_encode($data);
    $ch = curl_init('https://www.zarinpal.com/pg/rest/WebGate/PaymentRequestWithExtra.json');
    curl_setopt($ch, CURLOPT_USERAGENT, 'ZarinPal Rest Api v1');
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Content-Length: ' . strlen($jsonData)
    ));
    $result = curl_exec($ch);
    $err = curl_error($ch);
    $result = json_decode($result, true);
    curl_close($ch);
    if ($err) {
        $response->getBody()->write("cURL Error #:" . $err);
        return;
    } else {
        if ($result["Status"] == 100) {
            return $response->withRedirect('https://www.zarinpal.com/pg/StartPay/' . $result["Authority"]);
        } else {
            $response->getBody()->write('ERR: ' . $result["Status"]);
            return;
        }
    }
})->setName('gopay');

$app->get('/{name}', function(Request $request, Response $response, $args) use ($twig, $app, $globalPrices) {
    $path = trim(urldecode($args["name"]));
    if ($path == "زبان_های_ترجمه_رسمی") {
        $response->getBody()->write($twig->render('lang.twig', ["app_name" => APP_NAME, "app_site" => APP_SITE, "user" => getCurrentUser()]));
        return;
    }
    if ($path == "تعیین_آدرس") {
        $response->getBody()->write($twig->render('address.twig', ["app_name" => APP_NAME, "app_site" => APP_SITE, "user" => getCurrentUser(), "addressList" => getUserAddressList()]));
        return;
    }
    if ($path == "لیست_دفاتر_ترجمه_رسمی") {
        $response->getBody()->write($twig->render('centers.twig', ["app_name" => APP_NAME, "app_site" => APP_SITE, "user" => getCurrentUser(), "globalPrices" => $globalPrices]));
        return;
    }
    if ($path == "پیوست_مدارک_ترجمه_رسمی") {
        $response->getBody()->write($twig->render('upload.twig', ["app_name" => APP_NAME, "app_site" => APP_SITE, "user" => getCurrentUser()]));
        return;
    }
    if ($path == "انتخاب_مدارک_ترجمه_رسمی") {
        return $response->withRedirect($app->getContainer()->get('router')->pathFor("main"));
    }
    if (substr($path, 0, 40) === "دفتر_ترجمه_رسمی_شماره_") {
        $array = explode("_", $path);
        $code = $array[count($array) - 2];
        $center = getCenterByCode($code);
        $response->getBody()->write($twig->render('center.twig', ["app_name" => APP_NAME, "app_site" => APP_SITE, "user" => getCurrentUser(), "center" => $center]));
        return;
    }
    $docType = getDocType($path);
    if ($docType != null) {
        $response->getBody()->write($twig->render('doc.twig', ["app_name" => APP_NAME, "app_site" => APP_SITE, "user" => getCurrentUser(), "doc" => $docType]));
        return;
    }
    $response->getBody()->write($twig->render('notfound.twig', ["app_name" => APP_NAME, "app_site" => APP_SITE]));
    return;
});

$app->run();

function getCarrierPrice($p1, $p2) {
    $loc1 = explode(",", $p1);
    $loc2 = explode(",", $p2);
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
    $token = "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJzdWIiOjI0MTEsImlzcyI6Imh0dHA6XC9cL3NhbmRib3gtcGFuZWwuYWxvcGV5ay5jb21cL2dlbmVyYXRlLXRva2VuXC8yNDExIiwiaWF0IjoxNTIzOTQ2NTE1LCJleHAiOjUxMjM5NTAxMTUsIm5iZiI6MTUyMzk0NjUxNSwianRpIjoiMjJlZTBkZmExYzlmOGM0MjRjMjc2ZDc1YjZkOTFkY2IifQ.2AiK11OVIXcpkwfLu5uFLobED8kn-ae3DSZKMZDE7Uo";
    curl_setopt_array($curl, [
        CURLOPT_URL => "https://sandbox-api.alopeyk.com/api/v2/orders/price/calc",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => json_encode(
                [
                    "transport_type" => "motor_taxi",
                    "addresses" => [
                        [
                            "type" => "origin",
                            "lat" => $loc1[0],
                            "lng" => $loc1[1],
                        ],
                        [
                            "type" => "destination",
                            "lat" => $loc2[0],
                            "lng" => $loc2[1],
                        ]
                    ],
                    "has_return" => true,
                    "cashed" => false,
                ]
        ),
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer " . $token,
            "Content-Type: application/json; charset=utf-8",
            "X-Requested-With: XMLHttpRequest"
        ],
    ]);

    $res = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);

    if ($err) {
        return -1;
    } else {
        $res = json_decode($res);
        $status = $res->status;
        if ($status == "fail") {
            return -1;
        } else {
            return $res->object->price;
        }
    }
}

function saveOrder($centerId, $addressId, $bill, $amount) {
    $conn = getConnection();
    $stmt = $conn->prepare("INSERT INTO the_order(user_id,center_id,address_id,bill,amount)VALUES(?,?,?,?,?)");
    $user = intval(getCurrentUser()["id"]);
    $centerId = intval($centerId);
    $addressId = intval($addressId);
    $stmt->bind_param("iiisi", $user, $centerId, $addressId, $bill, $amount);
    $stmt->execute();
    $result = $stmt->get_result();
    $last_id = $conn->insert_id;
    $stmt->close();
    $conn->close();
    return $last_id;
}

function verifyOrder($orderId, $paycode) {
    $conn = getConnection();
    $stmt = $conn->prepare("UPDATE the_order SET paycode=? WHERE id=?");
    $stmt->bind_param("is", $paycode, $orderId);
    $stmt->execute();
    $error = $stmt->error;
    $stmt->close();
    $conn->close();
    return $error;
}

function getProductPrice($product, $globalPrices) {
    $conn = getConnection();
    $stmt = $conn->prepare("SELECT * FROM product WHERE id=?");
    $stmt->bind_param("i", $product->product->id);
    $stmt->execute();
    $result = $stmt->get_result();
    if (mysqli_num_rows($result) == 0) {
        return -1;
    }
    $row = $result->fetch_assoc();

    $total = 0;
    $translateBase = $row["price"];
    if ($product->lang !== "1") {
        $translateBase = $product->$row["extra"];
    }
    $stmt->close();
    $conn->close();
    $pages = intval($product->pages);
    if ($pages > 1) {
        $total = $translateBase * $pages;
    } else {
        $total = $translateBase;
    }
    if ($product->marriage) {
        $total+=$globalPrices["shenas_item"];
    }
    if ($product->talagh) {
        $total+=$globalPrices["shenas_item"];
    }
    if ($product->wifeDie) {
        $total+=$globalPrices["shenas_item"];
    }
    if ($product->die) {
        $total+=$globalPrices["shenas_item"];
    }
    if ($product->desc) {
        $total+=$globalPrices["shenas_item"];
    }
    $total+=$globalPrices["shenas_item"] * $product->childCount;
    $total+=$globalPrices["passport"] * ($product->mohrCount + $product->vizaCount);
    $total+=$globalPrices["enteghal"] * ($product->entghalCount);
    if ($product->clone !== "1") {
        $total+=(intval($product->clone) - 1) * $total / 4;
    }
    return $total;
}

function getUserAddressList() {
    $conn = getConnection();
    $stmt = $conn->prepare("SELECT * FROM address LEFT JOIN user_address ON user_address.user_id=? AND address.id=user_address.address_id");
    $stmt->bind_param("i", getCurrentUser()["id"]);
    $stmt->execute();
    $result = $stmt->get_result();
    $list = array();
    while ($row = $result->fetch_assoc()) {
        array_push($list, [ "address" => $row["address"], "mobile" => $row["mobile"], "point" => $row["point"], "name" => $row["name"], "id" => $row["address_id"]]);
    }
    $stmt->close();
    $conn->close();
    return $list;
}

function getDocType($url) {
    $conn = getConnection();
    $stmt = $conn->prepare("SELECT * FROM product WHERE url=?");
    $stmt->bind_param("s", $url);
    $stmt->execute();
    $result = $stmt->get_result();
    if (mysqli_num_rows($result) == 0) {
        return null;
    }
    $row = $result->fetch_assoc();
    $stmt->close();
    $conn->close();
    return ["id" => $row["id"], "title" => $row["title"], "price" => $row["price"], "extra" => $row["extra"], "desc" => $row["desc"], "url" => $row["url"], "category" => $row["category"], "fi" => $row["fi"]];
}

function isLogged() {
    return getCurrentUser() != null;
}

function isManagerLogged() {
    return getCurrentManager() != null;
}

function setCurrentUser($name, $id, $mail, $mobile) {
    $_SESSION["user"] = ["name" => $name, "id" => $id, "mail" => $mail, "mobile" => $mobile];
}

function setCurrentManager($center) {
    $_SESSION["manager"] = $center;
}

function getCurrentUser() {
    if (isset($_SESSION["user"])) {
        return $_SESSION["user"];
    }
    return null;
}

function getCurrentManager() {
    if (isset($_SESSION["manager"])) {
        return $_SESSION["manager"];
    }
    return null;
}

function getConnection() {
    $conn = new mysqli(HOST, USER, PASSWORD, DB_NAME);
    $conn->set_charset("utf8");
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    return $conn;
}

function sendMail($to, $message, $title, $twig) {
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= 'From: <info@' + APP_SITE + '>' . "\r\n";
    $body = $twig->render('mail.twig', ["title" => $title, "message" => $message, "app_site" => APP_SITE, "app_name" => APP_NAME, "app_site" => APP_SITE]);
    mail($to, $title, $body, $headers);
}

function getElasticCenter($id) {
    $header = array(
        "content-type: application/json",
        "Authorization: Basic NmI5bzlsOWg3NDp5cWVnbnpkY2N2"
    );

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($curl, CURLOPT_URL, ELASTIC_HOST . "/centers/_doc/" . $id);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $res = curl_exec($curl);
    $error = curl_error($curl);
    curl_close($curl);
    $res = json_decode($res);
    $res = $res->_source;
    $res->id = $id;
    return $res;
}

function incCenterScore($id) {
    $header = array(
        "content-type: application/json",
        "Authorization: Basic NmI5bzlsOWg3NDp5cWVnbnpkY2N2"
    );

    $param = '
    {
        "script" : {
            "lang": "painless",
            "source":"ctx._source.score++"
        }
    }';

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($curl, CURLOPT_URL, ELASTIC_HOST . "/centers/_update/" . $id);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $param);
    $res = curl_exec($curl);
    $error = curl_error($curl);
    curl_close($curl);
    $res = json_decode($res);
    $res = $res->_source;
    $res->id = $id;
    return $res;
}

function getCenterByCode($link) {
    $header = array(
        "content-type: application/json",
        "Authorization: Basic NmI5bzlsOWg3NDp5cWVnbnpkY2N2"
    );

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($curl, CURLOPT_URL, ELASTIC_HOST . "/centers/_search?q=code:" . $link);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $res = curl_exec($curl);
    $error = curl_error($curl);
    curl_close($curl);
    $res = json_decode($res);
    $res = $res->hits;
    $res = $res->hits;
    if (count($res) == 0) {
        return null;
    }
    $id = $res[0]->_id;
    $res = $res[0]->_source;
    $res->id = $id;
    return $res;
}

function getCenterByLink($link) {
    $header = array(
        "content-type: application/json",
        "Authorization: Basic NmI5bzlsOWg3NDp5cWVnbnpkY2N2"
    );

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($curl, CURLOPT_URL, ELASTIC_HOST . "/centers/_search?q=link:" . $link);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $res = curl_exec($curl);
    $error = curl_error($curl);
    curl_close($curl);
    $res = json_decode($res);
    $res = $res->hits;
    $res = $res->hits;
    if (count($res) == 0) {
        return null;
    }
    $id = $res[0]->_id;
    $res = $res[0]->_source;
    $res->id = $id;
    return $res;
}

function getCenterByAuth($username, $password) {
    $header = array(
        "content-type: application/json",
        "Authorization: Basic NmI5bzlsOWg3NDp5cWVnbnpkY2N2"
    );

    $param = '
        {
            "query":{
                "bool":{
                    "filter":[
                        {
                            "term":{
                                "username":"' . $username . '"
                            }
                        },
                        {
                            "term":{
                                "password":"' . $password . '"
                            }
                        }
                    ]
                }
            }
        }';

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($curl, CURLOPT_URL, ELASTIC_HOST . "/centers/_search");
    curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $param);
    $res = curl_exec($curl);
    $error = curl_error($curl);
    curl_close($curl);
    $res = json_decode($res);

    $res = $res->hits;
    $res = $res->hits;
    if (count($res) == 0) {
        return null;
    }
    $id = $res[0]->_id;
    $res = $res[0]->_source;
    $res->id = $id;
    return $res;
}

function getElasticCenters($point, $sort) {
    $header = array(
        "content-type: application/json",
        "Authorization: Basic NmI5bzlsOWg3NDp5cWVnbnpkY2N2"
    );
    $param = '
        {
            "query" : {
                        "geo_distance":{
                                "distance":"200km",
                                "loc":"' . $point . '"
                        }
            },
                        "sort" : [
                                {
                                        "_geo_distance" : {
                                                "loc" : "' . $point . '",
                                                "order" : "asc",
                                                "unit" : "km",
                                                "mode" : "min",
                                                "distance_type" : "arc",
                                                "ignore_unmapped": true
                                        }
                                }
                        ]
        }';
    if ($sort == "score") {
        $param = '
        {
            "query" : {
                        "match_all":{}
            },
                        "sort" : [
                                {
                                    "score":{
                                        "order":"desc"
                                    }
                                }
                        ]
        }';
    } else if ($sort == "duration") {
        $param = '
        {
            "query" : {
                        "match_all":{}
            },
                        "sort" : [
                                {
                                    "duration":{
                                        "order":"asc"
                                    }
                                }
                        ]
        }';
    }



    $curl = curl_init();
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($curl, CURLOPT_URL, ELASTIC_HOST . "/centers/_search");
    curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $param);
    $res = curl_exec($curl);
    $error = curl_error($curl);
    curl_close($curl);
    $res = json_decode($res);
    $res = $res->hits;
    $res = $res->hits;
    $array = [];
    foreach ($res as $item) {
        $correct = $item->_source;
        $correct->id = $item->_id;
        $correct->raw_sort = $item->sort[0];
        $correct->sort = getDistance($item->sort[0]);
        unset($correct->username);
        unset($correct->password);
        unset($correct->link);
        array_push($array, $correct);
    }
    return $array;
}

function updateCenter($id, $mail, $account, $carrier, $duration, $license, $javaz, $password) {
    $header = array(
        "content-type: application/json",
        "Authorization: Basic NmI5bzlsOWg3NDp5cWVnbnpkY2N2"
    );

    $param = '
        {
            "doc":{
                "mail":"' . $mail . '",
                "account":"' . $account . '",
                "carrier":"' . $carrier . '",
                "duration":' . $duration . ',
                "license":"' . $license . '",
                "password":"' . $password . '",    
                "javaz":"' . $javaz . '"
            }
        }';

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($curl, CURLOPT_URL, ELASTIC_HOST . "/centers/_update/" . $id);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $param);
    $res = curl_exec($curl);
    $error = curl_error($curl);
    curl_close($curl);
}

function getDistance($sort) {
    if ($sort < 1.0) {
        return round($sort * 1000) . " متر";
    } else {
        return sprintf("%.2f", $sort) . " کیلومتر";
    }
}
