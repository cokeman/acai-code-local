<?
$menu = "cms_api_plugin";
$external = true;

$curl = curl_init();

// tokenHash : base64(user:sha1(password):domainNum)

curl_setopt_array($curl, array(
  CURLOPT_URL => "https://ws.cocosolution.com/api/auth/",
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => "",
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 0,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => "POST",
  CURLOPT_HTTPHEADER => array(
    "Content-length: 0",
    "Content-type: application/json",
    "Authorization: Login ".@$_REQUEST["tokenHash"]
  ),
));

$response = json_decode(curl_exec($curl),true);
$CURRENT_USER = $response["data"]["user"];

function userHasAllAdminAccess(){
    global $response;
    return @$response["data"]["user"]["accessList"]["all"]["accessLevel"] == 9 ? true : false;
}
function userHasSectionReadAccess($url){
    global $response;
  
    if (@$response["data"]["user"]["accessList"][$url]["accessLevel"] == 3) {
      return true;
    }else {
      return false;
    }
}
function userHasSectionAdminAccess($url){
    global $response;
  
    if (@$response["data"]["user"]["accessList"][$url]["accessLevel"] == 9) {
      return true;
    }else {
      return false;
    }
}
function userHasSectionWriterAccess($url){
    global $response;
  
    if (@$response["data"]["user"]["accessList"][$url]["accessLevel"] == 9) {
      return true;
    }else {
      return false;
    }
}
?>
<link rel="stylesheet" type="text/css" href="https://cms.cocosolution.com/css/tailwind.min.css">
<style>.containesr{margin-left:20px;}</style>
<div class="containesr mx-auto">
<?
include(__DIR__."/admin_actionHandler.php");
?>
</div>