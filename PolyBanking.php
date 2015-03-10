<?php

/**
 * Class PolyBanking
 */
class PolyBanking {    
    protected $server = null;
    protected $configID = null;
    protected $keyRequest = null;
    protected $keyAPI = null;
    protected $keyIPN = null;
    
    /**
     * Standard constructor
     */
    public function __construct($server, $configID, $keyRequest, $keyAPI, $keyIPN){
        if (substr($server, -1) != '/') {
          // Assure a trailing slash.
          $server .= '/';
        }
        $this->server = $server;
        $this->configID = $configID;
        $this->keyRequest = $keyRequest;
        $this->keyAPI = $keyAPI;
        $this->keyIPN = $keyIPN;
    }

    /**
     * Function to compute a signature from an associative array.
     * (replaces '=' with '!!' and ';' with '??', then concatenates key=value;secret;key1=value1;secret; etc)
     * @param: $secret, the key you want to hash with; $data, the data you want hashed.
     * @return: A hash of the $secret and $data following the official PolyBanking API spec.
     */
    function compute_sig($secret, $data){
        
        function escape_for_signature($str){
            return str_replace(array('=', ';'), array('!!', '??'), $str);
        }
        
        $sig = "";
        
        foreach($data as $key => $value){
            $sig .= escape_for_signature($key);
            $sig .= '=';
            $sig .= escape_for_signature($value);
            $sig .= ';';
            $sig .= $secret;
            $sig .= ';';
        }
        
        return openssl_digest($sig, 'sha512');    
    }
    
    /**
     *  POST an array to a certain url
     *  @param: $url, the url to post to; $data, an associative array to be posted
     *  @return: the server's response.
     */
    function post_curl($url, $data){
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        //curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        //curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        
        $result = curl_exec($ch);

        curl_close($ch);
        return json_decode($result);
    }
    
    /**
     * Send off a new transaction to the PolyBanking server,
     * then send off the user to their payment site when PolyBanking gives the OK.
     * @param: $amount = amount in CHF, centimes
     * @return: an array of the transaction status and the url to send the user to.
     *
     */
    function new_transaction($amount, $reference, $extra_data = ''){       
        $data['amount'] = $amount;
        $data['reference'] = $reference;
        $data['extra_data'] = $extra_data;
        $data['config_id'] = $this->configID;
        
        $data['sign'] = $this->compute_sig($this->keyRequest, $data);
        
        $url = $this->server . "paiements/start/";
        
        $result = $this->post_curl($url, $data);
        
        return $result;
    }
    
    /**
     * Get the details of a specific transaction via its reference
     * 
     * @param: $reference, the reference of the transaction you want details for.
     * @return: (reference, extra_data, amount, postfinance_id, postfinance_status, internal_status, ipn_needed,
     *   creation_date, last_userforwarded_date, last_user_back_from_postfinance_date, last_postfinance_ipn_date,
     *  last_ipn_date, postfinance_status_text, internal_status_text). See details @ official API spec.
     */
    function get_transaction($reference){
        if($reference = null){
            die("Reference can't be null");
        }
        
        $url = $this->server . "api/transactions/". $reference . "/";
        
        $data['config_id'] = $this->configID;
        $data['secret'] = $this->keyAPI;
        
        //$result now contains all details of the transaction.
        return $this->post_curl($url, $data);
    }
    
    
    /**
     * Get a list references of transactions
     *
     * @param: $max_transaction, the number of reference you want to be returned
     * @return: an array of (reference='ref1', reference='ref2', etc);
     * (yes it should be $max_transactions and not $max_transaction, but we're following official API standards...)
     */
    function get_transactions($max_transaction = 100){
        $url = $this->server . "api/transactions/";
        
        $data['config_id'] = $this->configID;
        $data['secret'] = $this->keyAPI;
        $data['max_transaction'] = $max_transaction;
        
        $result = $this->post_curl($url, $data);
        
        if($result->result != "ok"){
            die('Could not get a list of transactions for an unknown reason.');
        }
        
        return $result->data;
    }
    
    /**
     * Show the logs for one transaction
     * @param: $reference, the reference of the transaction you want the logs of.
     * @return (when, extra_data, log_type, log_type_text). See details @ official API spec.
     */
    function transaction_show_logs($reference){
        if($reference = null){
            die("Reference can't be null");
        }
        
        $url = $this->server . "api/transactions/" . $reference . "/logs/";
        
        $data['config_id'] = $this->configID;
        $data['secret'] = $this->keyAPI;
        
        $result = $this->post_curl($url, $data);
        
        if($result->result != "ok"){
            die('Could not get a list of transactions for an unknown reason.');
        }
        
        return $result->data;
    }
    
    /**
     * The PolyBanking server calls our IPN URL to send an IPN notification.
     * This function checks whether the IPN is correct, to see if the transaction was ok (TODO: is this description correct?)
     * @params: POST: config, reference, postfinance_status, postfinance_status_good, last_update
     * @return: (is_ok, message, reference, postfinance_status, postfinance_status_good, last_update). See details @ official API spec.
     */
    function check_ipn(){
        foreach($_POST as $key => $value){
            if($key != "sign"){
                $data[$key] = $value;
            }
        }
        
        if($_POST['sign'] != $this->compute_sig($this->keyIPN, $data)){
            return array(false, 'SIGN', null, null, null, null);
        }
        if($data['config_id'] != $this->configID){
            return array(false, 'CONFIG', null, null, null, null);
        }
        
        //TODO: format last_update    
        return array(true, '', $data['reference'], $data['postfinance_status'], $data['postfinance_status_good'] == 'True', $data['last_update']);
    }

}

?>