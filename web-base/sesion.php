<?
require __DIR__.'/vendor/autoload.php'; 
require_once(dirname(__FILE__)."/cms/lib/plugins/cms_api/v3/custom_classes/QuantumAPI.class.php");

use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

if (session_status() == PHP_SESSION_NONE) { session_start(); }

$num = (@$num) ? $num : @$_REQUEST["num"];
$familia = (@$familia) ? $familia : @$_REQUEST["familia"];
if ($familia) $_REQUEST["familia"] = $familia;
if ($num) $_REQUEST["num"] = $num;

require_once(dirname(__FILE__)."/lib/Module.php");
require_once(dirname(__FILE__)."/lib/Resource.class.php");
require_once(dirname(__FILE__)."/lib/variables.php");
require_once(dirname(__FILE__)."/lib/minifier.php");
require_once(dirname(__FILE__)."/idiomas/espanol.php");
require_once __DIR__.'/cms/lib/plugins/payments/autoload.php';
require_once __DIR__.'/lib/IPNGenerica.php';

if (!isset($_SESSION["user"])) {$_SESSION["user"]=null;}

if (isset($_GET["cerrarsesion"])){
	unset($_SESSION["user"]);
	setcookie('quantum-token', '', time() - 3600, '/');
	session_destroy();
}

$keyToken = 'Analiticaempresas17'; // Debes almacenar esta clave de forma segura
$tiempoDeToken = 24 * 60 * 60; // 24 horas en segundos
$tiempoRenovacion = 30 * 60; // 30 minutos en segundos

if (@$_SESSION["user"] && @$_SESSION["user_bd"]){
	
	$filteredUser = array_intersect_key($_SESSION["user_bd"], array_flip(["num", "nombre","telefono","activado","correo","dominio","chatbot_conf","uuid","google_id","chatbot_hash","plan","rol"]));
	$filteredUser["hashDomain"] = sha1($filteredUser["chatbot_conf"]);
	$filteredUser["plan"] = intval(@$filteredUser["plan"]);
	QuantumAPI::getQuantumPlanData($filteredUser);
	
	$payload = [
	    'iat' => time(),
	    'exp' => time() + $tiempoDeToken, // Expira en 24 horas
	    'data' => [
	        'user' => $filteredUser // Información adicional si es necesario
	    ]
	];

	$jwt = JWT::encode($payload, $keyToken, 'HS256');
	setcookie('quantum-token', $jwt, time() + $tiempoDeToken, '/', '', false, false);
	
}

if (@$_GET["validateToken"]){
	$token = $_GET["validateToken"];

	header('Content-Type: application/json');

	if (@$token == "devMode"){
		$filteredUser = getQuantumUserData(122);//K-Tuin
		die(json_encode(['data' => ['user' => $filteredUser,'devMode' => true]]));
	}

	if (@$token == "devMode2"){
		$filteredUser = getQuantumUserData(1);//QuantumAsis
		die(json_encode(['data' => ['user' => $filteredUser,'devMode' => true]]));
	}

	try {
	    $decoded = JWT::decode($token, new Key($keyToken, 'HS256'));

		if (@$decoded->data->user->num) {
			$decoded->data = ["user" => getQuantumUserData($decoded->data->user->num)];
		}
		
	    // Verificar que el token esté próximo a expirar (menos de 1 minuto)
	    $remainingTime = $decoded->exp - time();

	    if ($remainingTime < $tiempoRenovacion) {
	        // Generar un nuevo token
	        $payload = [
	            'iat' => time(),
	            'exp' => time() + $tiempoDeToken, // Expira en 24 horas
	            'data' => $decoded->data
	        ];

	        $new_jwt = JWT::encode($payload, $keyToken, 'HS256');

			$decoded->data["token"] = $new_jwt;
	        setcookie('quantum-token', $new_jwt, time() + $tiempoDeToken, '/', '', false, false);
	        echo json_encode(['data' => $decoded->data]);
	    } else {
	        echo json_encode(['data' => $decoded->data]);
	    }
	} catch (Exception $e) {
		setcookie('quantum-token', '', time() - 3600, '/');
	    echo json_encode(['error' => 'Token inválido o expirado']);
	}
	die();
}

function getQuantumUserData($num){
	$usuario = CocoDB::get("usuarios u","u.num=$num",null,1,["ignoreSchema" => true,"aggregates" => [
		"(SELECT cf.dominio FROM cms_chatbots ch, cms_chat_configuraciones cf WHERE ch.usuario = u.num AND cf.num = ch.chat_conf_num LIMIT 1) as dominio",
		"(SELECT cf.num FROM cms_chatbots ch, cms_chat_configuraciones cf WHERE ch.usuario = u.num AND cf.num = ch.chat_conf_num LIMIT 1) as chatbot_conf",
		"(SELECT ch.rol FROM cms_chatbots ch WHERE ch.usuario = u.num LIMIT 1) as rol",
		"(SELECT ch.activo FROM cms_chatbots ch WHERE ch.usuario = u.num LIMIT 1) as permisoUso",
		"(SELECT cf.hash FROM cms_chatbots ch, cms_chat_configuraciones cf WHERE ch.usuario = u.num AND cf.num = ch.chat_conf_num LIMIT 1) as chatbot_hash",
		"(SELECT cf.plan FROM cms_chatbots ch, cms_chat_configuraciones cf WHERE ch.usuario = u.num AND cf.num = ch.chat_conf_num LIMIT 1) as plan"]
	])[0];
	$filteredUser = array_intersect_key($usuario, array_flip(["num", "nombre","permisoUso","telefono","activado","correo","dominio","chatbot_conf","uuid","google_id","chatbot_hash","plan","rol","isSuperAdmin"]));
	$filteredUser["hashDomain"] = sha1($filteredUser["chatbot_conf"]);
	$filteredUser["plan"] = intval(@$filteredUser["plan"]);
	$filteredUser["activado"] = intval(@$filteredUser["activado"]);
	$filteredUser["permisoUso"] = intval(@$filteredUser["permisoUso"]);

	QuantumAPI::getQuantumPlanData($filteredUser);
	
	//$filteredUser["plan_bd"] = $contrataciones;
	checkMinimalOptions($filteredUser);
	return $filteredUser;
}

/*if (@$_REQUEST["pruebas"]){
	header('Content-Type: application/json');
	$data = QuantumAPI::getJsonData(["domain" => "quantumasis.com","returnResult" => true]);
	die(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}*/

function checkMinimalOptions($user){
	$info_general = @CocoDB::get("cocouser_configs","chat_conf_num='".$user["chatbot_conf"]."' AND `key`='info_general'",null,1,["prefix" => "","ignoreSchema" => true])[0];
	if (!@$info_general){
		$dataPrepared = ["historial" => "36","ubicacion" => "Atlantic/Canary"];
		CocoDB::insertRecords("cocouser_configs",["chat_conf_num" => $user["chatbot_conf"], "key" => "info_general", "value" => json_encode($dataPrepared)],null,["prefix" => "","ignoreSchema" => true]);
	}

	$ia_config_checks = @CocoDB::get("cocouser_configs","chat_conf_num='".$user["chatbot_conf"]."' AND `key`='ia_config_checks'",null,1,["prefix" => "","ignoreSchema" => true])[0];
	if (!@$ia_config_checks){
		$dataPrepared = ["contextEnabled" => false,"functionsEnabled" => false];
		CocoDB::insertRecords("cocouser_configs",["chat_conf_num" => $user["chatbot_conf"], "key" => "ia_config_checks", "value" => json_encode($dataPrepared)],null,["prefix" => "","ignoreSchema" => true]);
	}

	$welcome_type = @CocoDB::get("cocouser_configs","chat_conf_num='".$user["chatbot_conf"]."' AND `key`='welcome_type'",null,1,["prefix" => "","ignoreSchema" => true])[0];
	if (!@$welcome_type){
		$dataPrepared = ["contextEnabled" => false,"functionsEnabled" => false];
		CocoDB::insertRecords("cocouser_configs",["chat_conf_num" => $user["chatbot_conf"], "key" => "welcome_type", "value" => "desactivado"],null,["prefix" => "","ignoreSchema" => true]);
	}

	$info_hours_text = '{"allWeekEnabled":false,"textHour":"","schedule":[{"day":"Lunes","internationalDay":"monday","start":"09:00","end":"17:00","active":false},{"day":"Martes","internationalDay":"tuesday","start":"09:00","end":"17:00","active":false},{"day":"Miércoles","internationalDay":"wednesday","start":"09:00","end":"17:00","active":false},{"day":"Jueves","internationalDay":"thursday","start":"05:00","end":"06:57","active":false},{"day":"Viernes","internationalDay":"friday","start":"09:00","end":"11:52","active":false},{"day":"Sábado","internationalDay":"saturday","start":"09:00","end":"17:00","active":false},{"day":"Domingo","internationalDay":"sunday","start":"09:00","end":"17:00","active":false}]}';
	$info_hours = @CocoDB::get("cocouser_configs","chat_conf_num='".$user["chatbot_conf"]."' AND `key`='info_hours'",null,1,["prefix" => "","ignoreSchema" => true])[0];
	if (!@$info_hours){
		$dataPrepared = ["contextEnabled" => false,"functionsEnabled" => false];
		CocoDB::insertRecords("cocouser_configs",["chat_conf_num" => $user["chatbot_conf"], "key" => "info_hours", "value" => $info_hours_text],null,["prefix" => "","ignoreSchema" => true]);
	}

	$widget_settings = @CocoDB::get("cocouser_configs","chat_conf_num='".$user["chatbot_conf"]."' AND `key`='widget_settings'",null,1,["prefix" => "","ignoreSchema" => true])[0];
	if (!@$widget_settings){
		$widget_settings = CocoDB::insertRecords("cocouser_configs",[ 'chat_conf_num' => $user["chatbot_conf"], 'key' => 'widget_settings', 'value' => json_encode([
			"ratingEnabled"=>false,
			"chatLanguage"=>"ES",
			"mobileNotification"=>"badgeOnly",
			"hideOfflineChat"=>false,
			"hideWidget"=>false,
			"canales_visibles" => [
				[
						"id" => 2,
						"icon" => "https:\/\/cdn-icons-png.flaticon.com\/512\/18099\/18099137.png ",
						"name" => "Web",
						"link" => "",
						"active" => true
				],
				[
						"id" => 1,
						"icon" => "https:\/\/cdn-icons-png.flaticon.com\/512\/4494\/4494494.png",
						"name" => "WhatsApp",
						"link" => "",
						"active" => false
				],
				[
						"id" => 4,
						"icon" => "https:\/\/cdn-icons-png.flaticon.com\/128\/3955\/3955024.png",
						"name" => "Instagram",
						"link" => "",
						"active" => false
				],
				[
						"id" => 3,
						"icon" => "https:\/\/quantumasis.com\/template\/estandar\/images\/messenger.png",
						"name" => "Messenger",
						"link" => "",
						"active" => false
				],
				[
						"id" => 5,
						"icon" => "https:\/\/quantumasis.com\/template\/estandar\/images\/telegram.png",
						"name" => "Telegram",
						"link" => "",
						"active" => false
				]
		]
		])],null,["prefix"=>"","ignoreSchema"=>true]);
	}

	$widget_forms = @CocoDB::get("cocouser_configs","chat_conf_num='".$user["chatbot_conf"]."' AND `key`='widget_forms'",null,1,["prefix" => "","ignoreSchema" => true])[0];
	if (!@$widget_forms){
		$widget_forms = CocoDB::insertRecords("cocouser_configs",[ 'chat_conf_num' => $user["chatbot_conf"], 'key' => 'widget_forms', 'value' => json_encode([
			"title"=>null,
			"description"=>null,
			"showOnlineForm"=>false,
			"email"=>false,
			"name"=>false,
			"phone"=>false,
			"privacyNoticeEnabled"=>false,
			"privacyNoticeCheckRequired"=>false,
			"privacyNoticeUrl"=>""
		])],null,["prefix"=>"","ignoreSchema"=>true]);
	}

	$widget_design = @CocoDB::get("cocouser_configs","chat_conf_num='".$user["chatbot_conf"]."' AND `key`='widget_design'",null,1,["prefix" => "","ignoreSchema" => true])[0];
	if (!@$widget_design){
		$widget_design = CocoDB::insertRecords("cocouser_configs",[ 'chat_conf_num' => $user["chatbot_conf"], 'key' => 'widget_design', 'value' => json_encode([
			"selectedColor"=>"#9629b8",
			"customColor"=>"#2196f3",
			"selectedGradient"=>"linear-gradient(45deg, rgb(243 214 214), rgb(255 94 0))",
			"customGradientColor1"=>"#ffccff",
			"customGradientColor2"=>"#0e6ead",
			"customGradient"=>"linear-gradient(45deg, #ffccff, #0e6ead)",
			"colorMode"=>"solidos",
			"position"=>"Izquierdo",
			"title"=>"¿Cómo podemos ayudar?",
			"status"=>"Escribe tu mensaje aquí",
			"titleOffline"=>"Estamos offline",
			"statusOffline"=>"Escribe tu mensaje aquí",
			"textArea"=>"Respondemos inmediatamente",
			"theme"=>"Burbuja",
			"bubbleText"=>"Chat"
		])],null,["prefix"=>"","ignoreSchema"=>true]);
	}


	$custom_domain = @CocoDB::get("cocouser_configs","chat_conf_num='".$user["chatbot_conf"]."' AND `key`='custom_domain'",null,1,["prefix" => "","ignoreSchema" => true])[0];
	if (!@$custom_domain){
		$custom_domain = CocoDB::insertRecords("cocouser_configs",[ 'chat_conf_num' => $user["chatbot_conf"], 'key' => 'custom_domain', 'value' => 'https://quantumasis.com/template/estandar/images/favicon.png'],null,["prefix"=>"","ignoreSchema"=>true]);
	}

	$start_messages = @CocoDB::get("cocouser_configs","chat_conf_num='".$user["chatbot_conf"]."' AND `key`='start_messages'",null,1,["prefix" => "","ignoreSchema" => true])[0];
	if (!@$start_messages){
		$start_messages = CocoDB::insertRecords("cocouser_configs",[ 'chat_conf_num' => $user["chatbot_conf"], 'key' => 'start_messages', 'value' => '[]'],null,["prefix"=>"","ignoreSchema"=>true]);
	}

	$help_messages = @CocoDB::get("cocouser_configs","chat_conf_num='".$user["chatbot_conf"]."' AND `key`='help_messages'",null,1,["prefix" => "","ignoreSchema" => true])[0];
	if (!@$help_messages){
		$help_messages = CocoDB::insertRecords("cocouser_configs",[ 'chat_conf_num' => $user["chatbot_conf"], 'key' => 'help_messages', 'value' => '[]'],null,["prefix"=>"","ignoreSchema"=>true]);
	}
	

}

function sesion_create_quantum_user($email,$name,$userid = null,$phone = null, $clave = null, $forceActivate = false, $sendEmail = true){
    $insert = CmsApi::insert(
        "usuarios",
        [
            [
                'activado' => $forceActivate ? 1 : 0,
                'correo' => $email,
            	'clave' => $clave ? sha1($clave) : sha1("google_".$userid),
            	'nombre' => $name,
				'google_id' => $userid,
                'telefono' => $phone
            ]
        ]
    );
               
    $inserted = isset($insert) && $insert == 1;
    if (!$inserted) {
    	return ['error' => t_var('Error desconocido 1')];
    }
    
    $user = CmsApi::get(
        'usuarios', 
        "correo LIKE '$email'"
    );
    
    $user = $user[0];
    
    $uuid = bin2hex(random_bytes(2)); // Generar UUID
    $dominioProvisional = $uuid . "." . "quantumasis.com";
    
    $insert = CmsApi::insert(
    "chat_configuraciones",
        [
            [
               'dominio' => $dominioProvisional
          
            ]
        ]
    );
    $chat_config = CmsApi::get(
        'chat_configuraciones', 
        "dominio = '$dominioProvisional'",
        'num ASC',
        1
    );
    
    $insert = CmsApi::insert(
        "chatbots",
        [
            [
              'usuario' => $user["num"],
              'chat_conf_num' => $chat_config[0]["num"],
              'rol' => 1, //Cambiar para que sea segun venga del front en la versión 2.0
              'activo' => $forceActivate ? 1 : 0
            ]
        ]
    );
    
    if ($sendEmail){
		hook("/hooks/customEmailHeader/",["remote" => $_SESSION["REMOTE_URL"]]);
		$userDB = @CocoDB::get("usuarios","num=".intval($usernum),"num desc",1)[0];
    	if (@$userDB){
        	$result3 = CocoEmail::send("REGISTRO",$userDB,[$userDB["correo"],"soporte@quantumasis.com"],null,null,false,["twig" => true]);
    	}
	}
	if ($userid){
    	return sesion_get_quantum_user($email,$userid);
	}else{
		return sesion_get_quantum_user($email,null,$clave,false,$user["num"]);
	}
}

function sesion_get_quantum_user($email = null,$userid = null,$password = null, $activado = false,$num = null){
	
    if (!$userid){
		if ($email) $where = "u.correo LIKE '".mysql_real_escape_string($email)."'";
		if ($num) $where = "u.num=".intval($num);
		if ($password) $where .= " and u.clave='".mysql_real_escape_string(sha1($password))."'";
		if ($activado) $where .= " and u.activado=1";
		
        $usuario = CocoDB::get(
            'usuarios u', 
            $where,
            null,
            1,
            [
                "ignoreSchema" => true,
                "aggregates" => [
                    "(SELECT cf.dominio FROM cms_chatbots ch, cms_chat_configuraciones cf WHERE ch.usuario = u.num AND cf.num = ch.chat_conf_num LIMIT 1) as dominio",
					"(SELECT ch.rol FROM cms_chatbots ch WHERE ch.usuario = u.num LIMIT 1) as rol",
                    "(SELECT cf.num FROM cms_chatbots ch, cms_chat_configuraciones cf WHERE ch.usuario = u.num AND cf.num = ch.chat_conf_num LIMIT 1) as chatbot_conf",
			        "(SELECT cf.hash FROM cms_chatbots ch, cms_chat_configuraciones cf WHERE ch.usuario = u.num AND cf.num = ch.chat_conf_num LIMIT 1) as chatbot_hash",
			        "(SELECT cf.plan FROM cms_chatbots ch, cms_chat_configuraciones cf WHERE ch.usuario = u.num AND cf.num = ch.chat_conf_num LIMIT 1) as plan"
				]
            ]
        );

    }else{

        $usuario = CocoDB::get(
            'usuarios u',
            "u.correo LIKE '".mysql_real_escape_string($email)."' and u.google_id='".$userid."'",
            null,
            1,
            [
                "ignoreSchema" => true,
                "aggregates" => [
                    "(SELECT cf.dominio FROM cms_chatbots ch, cms_chat_configuraciones cf WHERE ch.usuario = u.num AND cf.num = ch.chat_conf_num LIMIT 1) as dominio",
					"(SELECT ch.rol FROM cms_chatbots ch WHERE ch.usuario = u.num LIMIT 1) as rol",
                    "(SELECT cf.num FROM cms_chatbots ch, cms_chat_configuraciones cf WHERE ch.usuario = u.num AND cf.num = ch.chat_conf_num LIMIT 1) as chatbot_conf",
			        "(SELECT cf.hash FROM cms_chatbots ch, cms_chat_configuraciones cf WHERE ch.usuario = u.num AND cf.num = ch.chat_conf_num LIMIT 1) as chatbot_hash",
			        "(SELECT cf.plan FROM cms_chatbots ch, cms_chat_configuraciones cf WHERE ch.usuario = u.num AND cf.num = ch.chat_conf_num LIMIT 1) as plan"
                ]
            ]
        );
    }
    
    $usuario = @$usuario[0];
    
    return $usuario;   
}
?>
