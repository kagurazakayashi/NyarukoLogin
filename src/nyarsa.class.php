<?php
class nyarsa {
    private $config = [
        "config" => "/www/server/php/73/src/ext/openssl/tests/openssl.cnf",
        "digest_alg" => "sha512",
        "private_key_bits" => 4096, //位元組
        "private_key_type" => OPENSSL_KEYTYPE_RSA //加密型別
    ];
    private $privateKeyPassword = null; //私鑰密碼
    public $privateKey = "";
    public $publicKey = "";
    /**
     * 建立私鑰和公鑰
     */
    public function createKey() {
        try {
            // 建立公鑰和私鑰
            $rsaRes = openssl_pkey_new($this->config);
            // 獲取私鑰給 privateKey
            openssl_pkey_export($rsaRes, $this->privateKey, $this->privateKeyPassword, $this->config);
            // 獲取公鑰給 publicKey
            $this->publicKey = openssl_pkey_get_details($rsaRes)["key"];
            // 釋放私鑰
            openssl_pkey_free($rsaRes);
        } catch (Exception $e) {
            die("Exception ".$e->getMessage());
        }
    }
    /**
     * 加密
     * @param String data 明文
     * @return String 密文
     */
    public function encrypt(string $data, bool $isPrivateKey=false):string {
        $encrypted = null;
        if ($isPrivateKey) {
            if ($this->privateKeyPassword) {
                $dkey = openssl_pkey_get_private($this->privateKey,$this->privateKeyPassword);
                openssl_private_encrypt($data, $encrypted, $dkey);
            } else {
                openssl_private_encrypt($data, $encrypted, $this->privateKey);
            }
        } else {
            openssl_public_encrypt($data, $encrypted, $this->publicKey);
        }
        $data = base64_encode($encrypted);
        // return $data;
        return str_replace(['+','/','='],['-','_',''],$data);
    }
    /**
     * 解密
     * @param String data 密文
     * @return String 明文
     */
    public function decrypt(string $data, bool $isPrivateKey=false) {
        $decrypted = null;
        $data = str_replace(['-','_'],['+','/'],$data);
        $data = base64_decode($data);
        if ($isPrivateKey) {
            if ($this->privateKeyPassword) {
                $dkey = openssl_pkey_get_private($this->privateKey,$this->privateKeyPassword);
                openssl_private_decrypt($data, $decrypted, $dkey);
            } else {
                openssl_private_decrypt($data, $decrypted, $this->privateKey);
            }
        } else {
            openssl_public_decrypt($data, $decrypted, $this->publicKey);
        }
        return $decrypted;
    }
}
?>