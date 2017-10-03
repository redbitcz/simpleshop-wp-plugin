<?php

/**
 * Třída pro práci WP Pluginem a SimpleShop
 *
 * @author Ing. Martin Dostál <martin@simpleshop.cz>
 * @version 1.1
 */
class VyfakturujAPI{

    protected $login = null;
    protected $apiHash = null;
    protected static $URL = 'https://api.vyfakturuj.cz/2.0/';
    protected $lastInfo = null;

    const METHOD_POST = 'post',
            METHOD_GET = 'get',
            METHOD_DELETE = 'delete',
            METHOD_PUT = 'put';

    public function __construct($login,$apiHash){
        $this->login = $login;
        $this->apiHash = $apiHash;
        if(substr($_SERVER['SERVER_NAME'],-2) === 'lc'){ // pokud jsme na localhostu
            self::$URL = 'http://api.vyfakturuj.czlc/2.0/';
        }
    }

    /**
     * Vrátí seznam všech produktu
     *
     * @return array
     */
    public function getProducts($args = array()){
        return $this->_get('product/?'.http_build_query($args));
    }

    public function initWPPlugin($domain){
        return $this->_post('wpplugin/init/',array('domain' => $domain,'plugin_version' => SSC_PLUGIN_VERSION));
    }

    /**
     * Testovací funkce pro ověření správného spojení se serverem
     *
     * @return array
     */
    public function test(){
        return $this->_get('test/');
    }

    /**
     * Test faktury v PDF.
     * Pošle data na server, vytvoří na serveru fakturu kterou ale neuloží a pošle zpět ve formátu PDF.
     * Pokud se podaří fakturu vytvořit, pak je poslána ve formátu PDF na výstup. Jinak je vráceno pole.
     *
     * @param array $data
     * @return array
     */
    public function test_invoice__asPdf($data){
        $result = $this->_post('test/invoice/download/',$data);
        if(array_key_exists('content',$result)){
            ob_end_clean();
            $content = base64_decode($result['content']);
            header("Cache-Control: public");
            $filename = $result['filename'];
            header("Content-Description: File Transfer");
            header("Content-Disposition: attachment; filename=\"".$filename.".pdf\"");
            header('Content-type: application/pdf');
            header("Content-Transfer-Encoding: binary");
            header("Content-Length: ".strlen($content));
            echo $content;
            exit;
        }
        return $result;
    }

    private function _connect($path,$method,$data = array()){
        $curl = curl_init();
        curl_setopt($curl,CURLOPT_URL,static::$URL.$path);
        curl_setopt($curl,CURLOPT_RETURNTRANSFER,TRUE);
        curl_setopt($curl,CURLOPT_FAILONERROR,FALSE);
        curl_setopt($curl,CURLOPT_HTTPAUTH,CURLAUTH_BASIC);
        curl_setopt($curl,CURLOPT_USERPWD,$this->login.':'.$this->apiHash);
        curl_setopt($curl,CURLOPT_SSL_VERIFYPEER,false);
        curl_setopt($curl,CURLOPT_SSL_VERIFYHOST,2);
        curl_setopt($curl,CURLOPT_HTTPHEADER,array('Content-Type: application/json'));

        switch($method){
            case self::METHOD_POST:
                curl_setopt($curl,CURLOPT_POST,TRUE);
                curl_setopt($curl,CURLOPT_POSTFIELDS,json_encode($data));
                break;
            case self::METHOD_PUT:
                curl_setopt($curl,CURLOPT_CUSTOMREQUEST,"PUT");
                curl_setopt($curl,CURLOPT_POSTFIELDS,json_encode($data));
                break;
            case self::METHOD_DELETE:
                curl_setopt($curl,CURLOPT_CUSTOMREQUEST,"DELETE");
                break;
        }

        $response = curl_exec($curl);
        $this->lastInfo = curl_getinfo($curl);
        $this->lastInfo['dataSend'] = $data;
        curl_close($curl);
        $return = json_decode($response,true);
        return is_array($return) ? $return : $response;
    }

    /**
     * Vrati informace o poslednim spojeni
     * @return array|null
     */
    public function getInfo(){
        return $this->lastInfo;
    }

    private function _get($path,$data = null){
        return $this->_connect($path,self::METHOD_GET,$data);
    }

    private function _post($path,$data = null){
        return $this->_connect($path,self::METHOD_POST,$data);
    }

    private function _put($path,$data = null){
        return $this->_connect($path,self::METHOD_PUT,$data);
    }

    private function _delete($path,$data = null){
        return $this->_connect($path,self::METHOD_DELETE,$data);
    }

}
