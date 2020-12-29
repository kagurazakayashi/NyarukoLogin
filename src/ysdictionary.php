<?php
class YSDictionary {
    // 雅詩字典對象，支援包含多個同名 key，原順序返回

    private $keys = array();
    private $vals = array();

    /**
     * @description: 從多個對象和鍵創建字典
     * @param Object,String vk 對象、鍵
     */
    function dictionaryWithObjectsAndKeys(...$vk):void {
        for ($i=0; $i < count($vk); $i++) {
            $n = $vk[$i];
            if ($i % 2 == 0){
				array_push($this->vals,$n);
			} else {
				array_push($this->keys,strval($n));
			}
        }
        $kc = count($this->keys);
        $vc = count($this->vals);
        if ($kc < $vc) array_push($kc,null);
        else if ($vc < $kc) array_push($vc,null);
    }

    /**
     * @description: 從關聯數組創建字典
     * @param Array a 關聯數組
     */
    function dictionaryWithIndexedArray(array $a):void {
        $this->keys = array_keys($a);
        for ($i=0; $i < count($this->keys); $i++) {
            $this->vals[$i] = $a[$this->keys[$i]];
        }
    }

    /**
     * @description: 添加對象
     * @param Object v 對象
     * @param String k 鍵
     */
    function addObjectForKey($v,string $k):void {
        array_push($this->vals,$v);
        array_push($this->keys,$k);
    }

    /**
     * @description: 替換對象
     * @param Object v 對象
     * @param String k 鍵
     */
    function replaceObjectForKey($v,string $k):void {
        for ($i=0; $i < count($this->keys); $i++) {
            $nk = $this->keys[$i];
            if (strcmp($nk,$k) == 0) {
                $this->vals[$i] = $v;
            }
        }
    }

    /**
     * @description: 替換或添加對象
     * @param Object v 對象
     * @param String k 鍵
     */
    function setObjectForKey($v,string $k):void {
        $r = false;
        for ($i=0; $i < count($this->keys); $i++) {
            $nk = $this->keys[$i];
            if (strcmp($nk,$k) == 0) {
                $this->vals[$i] = $v;
                $r = true;
            }
        }
        if (!$r) {
            array_push($this->vals,$v);
            array_push($this->keys,$k);
        }
    }

    /**
     * @description: 獲取對象
     * @param String k 鍵
     */
    function objectForKey(string $k) {
        for ($i=0; $i < count($this->keys); $i++) {
            $nk = $this->keys[$i];
            if (strcmp($nk,$k) == 0) {
                return $this->vals[$i];
            }
        }
    }

    /**
     * @description: 獲取多個對象
     * @param String k 鍵
     */
    function objectsForKey(string $k):array {
        $r = [];
        for ($i=0; $i < count($this->keys); $i++) {
            $nk = $this->keys[$i];
            if (strcmp($nk,$k) == 0) {
                array_push($r,$this->vals[$i]);
            }
        }
        return $r;
    }

    /**
     * @description: 移除對象
     * @param String k 鍵
     */
    function removeObjectForKey(string $k):void {
        $rm = function(int $i) {
            $nk = [];
            $nv = [];
            for ($j=0; $j < count($this->keys); $j++) {
                if ($i == $j) continue;
                $nk[$j] = $this->keys[$j];
                $nv[$j] = $this->vals[$j];
            }
            $this->keys = $nk;
            $this->vals = $nv;
        };
        for ($i=0; $i < count($this->keys); $i++) {
            $nk = $this->keys[$i];
            if (strcmp($nk,$k) == 0) {
                $rm($i);
            }
        }
    }

    /**
     * @description: 移除多個對象
     * @param Array<String> ks 鍵數組
     */
    function removeObjectsForKeys(array $ks):void {
        foreach ($ks as $k) {
            $this->removeObjectForKey($k);
        }
    }

    /**
     * @description: 移除所有對象
     */
    function removeAllObjects():void {
        $this->keys = null; unset($this->keys);
        $this->vals = null; unset($this->vals);
        $this->keys = array();
        $this->vals = array();
    }

    /**
     * @description: 從另一個字典創建字典
     * @param YSDictionary dic 字典
     */
    function setDictionary(YSDictionary $dic) {
        $this->keys = $dic->keys;
        $this->vals = $dic->vals;
    }

    /**
     * @description: 字典中的對象數量
     * @return Int 字典中的對象數量
     */
    function count():int {
        return count($this->keys);
    }

    /**
     * @description: 獲取所有鍵
     * @return Array<String> 鍵數組
     */
    function allKeys():array {
        return $this->keys;
    }

    /**
     * @description: 獲取所有對象
     * @return Array<Object> 對象數組
     */
    function allValues():array {
        return $this->vals;
    }

    /**
     * @description: 析構
     */
    function __destruct() {
        $this->keys = null; unset($this->keys);
        $this->vals = null; unset($this->vals);
    }
}
