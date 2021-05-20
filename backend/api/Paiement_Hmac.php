<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json");
header("Access-Control-Allow-Headers: Access-Control-Allow-Headers, Access-Control-Allow-Methods, Content-Type, Authorization, X-Requested-With");
include_once "../../config/Database.php";
include_once "../../models/Options.php";

$db = new Database();
$conn = $db->connect();
$dataPayment = new Options($conn);

//Donn�es Mouttec
$dataPayment = $dataPayment->getOptions();
$counter = $dataPayment->rowCount();
$row = $payments_array->fetch();
extract($row);

//Donn�es commande
$decodedData = json_decode(file_get_contents("php://input"));
$cmd = $decodedData->cmd;
$porteur = $decodedData->emailCustomer;
$total = $decodedData->totalAmount;

// --------------- VARIABLES A MODIFIER ---------------

// Ennonciation de variables
$pbx_site = $site;									//variable de test 1999888
$pbx_rang = $rang;									//variable de test 32
$pbx_identifiant = $identifiant;				//variable de test 3
$pbx_cmd = $cmd;								//variable de test cmd_test1
$pbx_porteur = $porteur;							//variable de test test@test.fr
$pbx_total = $total;									//variable de test 100
// Suppression des points ou virgules dans le montant						
	$pbx_total = str_replace(",", "", $pbx_total);
	$pbx_total = str_replace(".", "", $pbx_total);

// Param�trage des urls de redirection apr�s paiement
$pbx_effectue = 'https://www.mouttec.com/success';
$pbx_annule = 'https://www.mouttec.com/erreur';
$pbx_refuse = 'https://www.mouttec.com/erreur';
// Param�trage de l'url de retour back office site
$pbx_repondre_a = 'https://www.mouttec.com';
// Param�trage du retour back office site
$pbx_retour = 'Mt:M;Ref:R;Auto:A;Erreur:E';

// Connection � la base de donn�es
// mysql_connect...
// On r�cup�re la cl� secr�te HMAC (stock�e dans une base de donn�es par exemple) et que l�on renseigne dans la variable $keyTest;
//$keyTest = '0123456789ABCDEF0123456789ABCDEF0123456789ABCDEF0123456789ABCDEF0123456789ABCDEF0123456789ABCDEF0123456789ABCDEF0123456789ABCDEF';
$keyTest = $key;



// --------------- TESTS DE DISPONIBILITE DES SERVEURS ---------------

$serveurs = array('tpeweb.paybox.com', //serveur primaire
'tpeweb1.paybox.com'); //serveur secondaire
$serveurOK = "";
//phpinfo(); <== voir paybox
foreach($serveurs as $serveur){
$doc = new DOMDocument();
$doc->loadHTMLFile('https://'.$serveur.'/load.html');
$server_status = "";
$element = $doc->getElementById('server_status');
if($element){
$server_status = $element->textContent;}
if($server_status == "OK"){
// Le serveur est pr�t et les services op�rationnels
$serveurOK = $serveur;
break;}
// else : La machine est disponible mais les services ne le sont pas.
}
//curl_close($ch); <== voir paybox
if(!$serveurOK){
die("Erreur : Aucun serveur n'a �t� trouv�");}
// Activation de l'univers de pr�production
//$serveurOK = 'preprod-tpeweb.paybox.com';

//Cr�ation de l'url cgi paybox
$serveurOK = 'https://'.$serveurOK.'/cgi/MYchoix_pagepaiement.cgi';
// echo $serveurOK;



// --------------- TRAITEMENT DES VARIABLES ---------------

// On r�cup�re la date au format ISO-8601
$dateTime = date("c");

// On cr�e la cha�ne � hacher sans URLencodage
$msg = "PBX_SITE=".$pbx_site.
"&PBX_RANG=".$pbx_rang.
"&PBX_IDENTIFIANT=".$pbx_identifiant.
"&PBX_TOTAL=".$pbx_total.
"&PBX_DEVISE=978".
"&PBX_CMD=".$pbx_cmd.
"&PBX_PORTEUR=".$pbx_porteur.
"&PBX_REPONDRE_A=".$pbx_repondre_a.
"&PBX_RETOUR=".$pbx_retour.
"&PBX_EFFECTUE=".$pbx_effectue.
"&PBX_ANNULE=".$pbx_annule.
"&PBX_REFUSE=".$pbx_refuse.
"&PBX_HASH=SHA512".
"&PBX_TIME=".$dateTime;
// echo $msg;

// Si la cl� est en ASCII, On la transforme en binaire
$binKey = pack("H*", $keyTest);

// On calcule l�empreinte (� renseigner dans le param�tre PBX_HMAC) gr�ce � la fonction hash_hmac et //
// la cl� binaire
// On envoi via la variable PBX_HASH l'algorithme de hachage qui a �t� utilis� (SHA512 dans ce cas)
// Pour afficher la liste des algorithmes disponibles sur votre environnement, d�commentez la ligne //
// suivante
// print_r(hash_algos());
$hmac = strtoupper(hash_hmac('sha512', $msg, $binKey));

// La cha�ne sera envoy�e en majuscule, d'o� l'utilisation de strtoupper()
// On cr�e le formulaire � envoyer
// ATTENTION : l'ordre des champs est extr�mement important, il doit
// correspondre exactement � l'ordre des champs dans la cha�ne hach�e
?>



<!------------------ ENVOI DES INFORMATIONS A PAYBOX (Formulaire) ------------------>
<form method="POST" action="<?php echo $serveurOK; ?>">
<input type="hidden" name="PBX_SITE" value="<?php echo $pbx_site; ?>">
<input type="hidden" name="PBX_RANG" value="<?php echo $pbx_rang; ?>">
<input type="hidden" name="PBX_IDENTIFIANT" value="<?php echo $pbx_identifiant; ?>">
<input type="hidden" name="PBX_TOTAL" value="<?php echo $pbx_total; ?>">
<input type="hidden" name="PBX_DEVISE" value="978">
<input type="hidden" name="PBX_CMD" value="<?php echo $pbx_cmd; ?>">
<input type="hidden" name="PBX_PORTEUR" value="<?php echo $pbx_porteur; ?>">
<input type="hidden" name="PBX_REPONDRE_A" value="<?php echo $pbx_repondre_a; ?>">
<input type="hidden" name="PBX_RETOUR" value="<?php echo $pbx_retour; ?>">
<input type="hidden" name="PBX_EFFECTUE" value="<?php echo $pbx_effectue; ?>">
<input type="hidden" name="PBX_ANNULE" value="<?php echo $pbx_annule; ?>">
<input type="hidden" name="PBX_REFUSE" value="<?php echo $pbx_refuse; ?>">
<input type="hidden" name="PBX_HASH" value="SHA512">
<input type="hidden" name="PBX_TIME" value="<?php echo $dateTime; ?>">
<input type="hidden" name="PBX_HMAC" value="<?php echo $hmac; ?>">
<input type="submit" value="Envoyer">
</form>