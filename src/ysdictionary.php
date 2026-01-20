<?php
declare(strict_types=1);

/**
 * 雅詩有序多鍵字典
 *
 * 支援包含多個同名鍵的字典物件，可原順序返回所有值。
 *
 * @package NyarukoLogin
 */
class YSDictionary {
    // 雅詩字典物件，支援包含多個同名 key，原順序返回

    private $keys = array();
    private $vals = array();

    /**
     * 從多個物件與鍵建立字典
     *
     * @param mixed ...$vk 物件與鍵的交替序列（值, 鍵, 值, 鍵, ...）
     */
    function dictionaryWithObjectsAndKeys(mixed ...$vk): void {
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
        if ($kc < $vc) array_push($this->keys, null);
        else if ($vc < $kc) array_push($this->vals, null);
    }

    /**
     * 從關聯陣列建立字典
     *
     * @param array $a 關聯陣列
     */
    function dictionaryWithIndexedArray(array $a): void {
        $this->keys = array_keys($a);
        for ($i=0; $i < count($this->keys); $i++) {
            $this->vals[$i] = $a[$this->keys[$i]];
        }
    }

    /**
     * 添加物件
     *
     * @param mixed  $v 物件
     * @param string $k 鍵
     */
    function addObjectForKey(mixed $v, string $k): void {
        array_push($this->vals,$v);
        array_push($this->keys,$k);
    }

    /**
     * 替換物件
     *
     * @param mixed  $v 物件
     * @param string $k 鍵
     */
    function replaceObjectForKey(mixed $v, string $k): void {
        for ($i=0; $i < count($this->keys); $i++) {
            $nk = $this->keys[$i];
            if (strcmp($nk,$k) == 0) {
                $this->vals[$i] = $v;
            }
        }
    }

    /**
     * 替換或添加物件
     *
     * @param mixed  $v 物件
     * @param string $k 鍵
     */
    function setObjectForKey(mixed $v, string $k): void {
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
     * 獲取物件
     *
     * @param string $k 鍵
     * @return mixed 對應的第一個物件，若不存在則返回 null
     */
    function objectForKey(string $k): mixed {
        for ($i=0; $i < count($this->keys); $i++) {
            $nk = $this->keys[$i];
            if (strcmp($nk,$k) == 0) {
                return $this->vals[$i];
            }
        }
    }

    /**
     * 獲取多個物件
     *
     * @param string $k 鍵
     * @return array 所有符合該鍵的物件陣列
     */
    function objectsForKey(string $k): array {
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
     * 移除物件
     *
     * @param string $k 鍵
     */
    function removeObjectForKey(string $k): void {
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
     * 移除多個物件
     *
     * @param string[] $ks 鍵陣列
     */
    function removeObjectsForKeys(array $ks): void {
        foreach ($ks as $k) {
            $this->removeObjectForKey($k);
        }
    }

    /**
     * 移除所有物件
     */
    function removeAllObjects(): void {
        $this->keys = null; unset($this->keys);
        $this->vals = null; unset($this->vals);
        $this->keys = array();
        $this->vals = array();
    }

    /**
     * 從另一個字典複製內容
     *
     * @param YSDictionary $dic 來源字典
     */
    function setDictionary(YSDictionary $dic): void {
        $this->keys = $dic->keys;
        $this->vals = $dic->vals;
    }

    /**
     * 字典中的物件數量
     *
     * @return int 物件數量
     */
    function count(): int {
        return count($this->keys);
    }

    /**
     * 獲取所有鍵
     *
     * @return string[] 鍵陣列
     */
    function allKeys(): array {
        return $this->keys;
    }

    /**
     * 獲取所有物件
     *
     * @return mixed[] 物件陣列
     */
    function allValues(): array {
        return $this->vals;
    }

    /**
     * 析構子
     */
    function __destruct() {
        $this->keys = null; unset($this->keys);
        $this->vals = null; unset($this->vals);
    }
}
