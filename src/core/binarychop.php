<?php
namespace Quick\Core;


class Binarychop
{
    public static function find($elements, $key, $min = 0) {
        $total = count($elements);

        // 如果是空元素列表，肯定是找不到
        if ($total < 1) {
            return FASLE;
        }

        // 查看包含的的直接索引，最快
        if (in_array($key, $elements)) {
            return $key;
        }

        $max = max($elements);

        // 如果不在允许的区域，直接返回找不到
        if ($key < $min || $key > $max) {
            return FALSE;
        }

        // 如果只有一个元素，这个元素就是最大值,一定在MIN或MAX之前，否则前面已进行过滤
        if (1 == $total) {
            return $max;
        } 

        // 找到左边元素个数
        $leftCount = ($total + 1) / 2;
        // 左边最后一个元素的索引是左边的个数-1， 假定这个数据是我们判断的中间值
        $mIndex = $leftCount - 1;
        // 取左边的最后一个元素的值，这个值一定不会等于key的，如果相等，前面就直接返回
        $mValue = $elements[$mIndex];

        // 如果是左边，就从左边找
        if ($mValue > $key) {
            return self::find(array_slice($elements, 0, $leftCount), $key);
        }

        // 右边的元素为去掉左边剩下的元素
        return self::find(array_slice($elements, $leftCount), $key, $mValue);
    }
}
