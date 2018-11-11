<?php
    $manual = "*** ERROR *** No se ingreso una variable requerida. <BR><BR> MANUAL DE USO <BR>Requeridos t, u, p <BR> <BR>u: Nombre de usuario FullCarga<BR>p: Contraseña de usuario FullCarga<BR>t: Tipo de operación <BR><BR><BR>t=0 : Saldo General<BR><BR><BR>t=1 : Recarga (requerido n, e, m)<BR><BR>__n: Número (sin 0 ni 15)<BR>__e: Empresa  (p = PERSONAL / c = CLARO / m = MOVISTAR / d = DIRECTV / t = TUENTI)<BR>__m: Monto (numero + ,)<BR><BR><BR>t=2 : Informe de repartos de crédito (requerido ini, fin)<BR><BR>__ini:Fecha inicio (formato dd-mm-yyyy)<BR>__fin:Fecha fin (formato dd-mm-yyyy)<BR><BR>";

    if(!isset($_GET['u'])  || !isset($_GET['p']) || !isset($_GET['t'])){
        echo $manual; 
        exit;
    }
    
    $USER             = $_GET['u'];
    $PASSWORD         = $_GET['p'];
    $TIPO             = $_GET['t'];

    if(($TIPO == 1) and (!isset($_GET['n']) || !isset($_GET['m'])  || !isset($_GET['e'])) or ($TIPO == 2) and (!isset($_GET['ini']) || !isset($_GET['fin']))){
        echo $manual; 
        exit;
    }

    $ch = curl_init('https://www.fullcarga-titan.com.ar/TITAN/Inicio.html');

    curl_setopt($ch, CURLOPT_USERAGENT,         "Mozilla/5.0 (Windows NT 6.1; WOW64; rv:52.0) Gecko/20100101 Firefox/52.0");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER,    TRUE);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION,    TRUE); 
    curl_setopt($ch, CURLOPT_POST,              TRUE); 
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,    FALSE);
    curl_setopt($ch, CURLOPT_COOKIEFILE,        'cookies.txt'); 
    curl_setopt($ch, CURLOPT_COOKIEJAR,         'cookies.txt'); 

    $fields = array(
        'struts.token.name'     =>      urlencode('token'),
        'token'                 =>      urlencode(Cortar(curl_exec($ch), 'token=', '">')),
        'usuario'               =>      urlencode($USER),
        'password'              =>      urlencode($PASSWORD)
    );

    curl_setopt($ch, CURLOPT_POSTFIELDS, unir($fields)); 
    curl_setopt($ch, CURLOPT_URL, 'https://www.fullcarga-titan.com.ar/TITAN/Login.html'); 

    if($TIPO==0){
        echo Cortar(curl_exec($ch),'Saldo General: $ ','<br/>');
        curl_close($ch);
        exit; 
    } 

    if($TIPO==2){
        $fields = array(
            'struts.token.name'     =>      urlencode('token'),
            'token'                 =>      urlencode(Cortar(curl_exec($ch), 'token=', '">')),
            'submit'                =>      urlencode(''),
            'tiporep'               =>      urlencode('I'),
            'fechaini'              =>      urlencode($_GET['ini']),
            'fechafin'              =>      urlencode($_GET['fin']),
            'formato'               =>      urlencode('HTML')
        );

        curl_setopt($ch, CURLOPT_POSTFIELDS, unir($fields)); 
        curl_setopt($ch, CURLOPT_URL, 'https://www.fullcarga-titan.com.ar/TITAN/informeRepartoCreditos.html');
      
        curl_exec($ch);

        curl_setopt($ch, CURLOPT_URL, 'https://www.fullcarga-titan.com.ar/TITAN/Informes.html');

        $dom    = new DOMDocument();  
        $dom->loadHTML(curl_exec($ch));  
        $tables = $dom->getElementsByTagName('table');   
        $rows   = $tables->item(0)->getElementsByTagName('tr');  

        $a = 0; foreach ($rows as $row){$a++;}

        for ($c = 9; $c < $a-1; $c++) {
            $i = 0 ;
            foreach ($rows->item($c+1)->getElementsByTagName('td') as $node){
                $i++;
                if($i===9){
                    echo $node->nodeValue.'<!>';
                }   
                elseif($i===10){
                    echo substr($node->nodeValue, 1,10).' '.substr($node->nodeValue, 11,8).'';
                }   
            }
            echo '<?>';
        }
        exit;
    }

    const radProd     = array("", "PRUNIFON", "CTIRECAR", "TELECOMP", "QUAM0002", "DIRECTV0");

    $OP = array_search($_GET['e'], array("", "m", "c", "p", "t", "d"));
    
    $fields = array(
        'struts.token.name'     =>      urlencode('token'),
        'token'                 =>      urlencode(Cortar(curl_exec($ch), 'token=', '">')),
        'submittedo'            =>      urlencode('TRUE'),
        'operadora'             =>      urlencode(($OP==4) ? '31' : $OP),
        'pais'                  =>      urlencode('AR'),
        'tipoproweb'            =>      urlencode('0'),
        'favorito'              =>      urlencode('')
    );

    curl_setopt($ch, CURLOPT_POSTFIELDS, unir($fields));     
    curl_setopt($ch, CURLOPT_URL, 'https://www.fullcarga-titan.com.ar/TITAN/tpvwebCargarProductos.html');

    $Result = curl_exec($ch); 

    $fields = array(
        'struts.token.name'     =>      urlencode('token'),
        'token'                 =>      urlencode(Cortar($Result, 'token=', '">')),
        'submitted'             =>      urlencode('Venta'),
        'ultVenta'              =>      urlencode(Cortar($Result, 'ultVenta" value="', '"/>')),
        'operativa'             =>      urlencode('V'),
        'objfocus'              =>      urlencode('param1&'),
        'errVenta'              =>      urlencode('0'),
        'tipoproweb'            =>      urlencode('0'),
        'tipoMonto'             =>      urlencode('0'),
        'RADPROD'               =>      urlencode(radProd[$OP]),
         radProd[$OP].'OPR'     =>      urlencode('V'),
        'param0'                =>      urlencode($_GET['n']),
        'param1'                =>      urlencode($_GET['m'])
    );

    curl_setopt($ch, CURLOPT_POSTFIELDS, unir($fields)); 
    curl_setopt($ch, CURLOPT_URL, 'https://www.fullcarga-titan.com.ar/TITAN/tpvweb.html'); 
    
    curl_exec  ($ch);
    curl_setopt($ch, CURLOPT_URL, 'https://www.fullcarga-titan.com.ar/TITAN/tpvwebticketPopUp.html'); 
    
    $Ticket = Cortar(curl_exec($ch),'9pt">','<br />');
    $Ticket =  str_replace('NAKGW', '', $Ticket);
    $Ticket =  str_replace('HS1021','', $Ticket);
    $Ticket =  str_replace('HS3005','', $Ticket);  //saldo insuficiente
    $Ticket =  str_replace('&nbsp;',' ', $Ticket);
    $Ticket =  str_replace('<BR/>','<BR>', $Ticket);
    
    echo $Ticket;
    
    $con=new PDO('mysql:host=localhost;dbname=id5636693_bddtc',"id5636693_root", "12345");$con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    try{
        $qry=$con->prepare("INSERT INTO cargas (fecha,usuario,password,numero,monto,empresa,response) VALUES (NOW(),'".$USER."','".$PASSWORD."','".$_GET['n']."','".$_GET['m']."','".$_GET['e']."','".$Ticket."');");
        $qry->execute();
        
    }catch(PDOException $e){
      //echo $e->getMessage();
     
    }
      

    function Cortar($Str, $ini, $fin){
        $uIni = strpos($Str, $ini);
        $uIniL = $uIni + strlen($ini);

        if ($uIni > -1 AND strpos($Str, $fin) > -1){    
            return substr($Str, $uIniL , strpos(substr($Str, $uIniL, strlen($Str)-$uIniL),$fin));
        }
    }

    function Unir($inputs){
        $fields_string = '';

        foreach($inputs as $key=>$value) {
            $fields_string .= $key.'='.$value.'&'; 
        }

        return rtrim($fields_string, '&');
    }

?>
