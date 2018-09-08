<?php

namespace elanpl\L3\Serializers;

class XMLSerializer implements ISerializer{
    public function Serialize($ViewModel)
    {
        return self::XSerialize($ViewModel->GetDTO());
    }

    /**
     * Get object class name without namespace
     * @param object $object Object to get class name from
     * @return string Class name without namespace
     */
    private static function GetClassNameWithoutNamespace($object) {
        $class_name = get_class($object);
        return end(explode('\\', $class_name));
    }

    /**
     * Converts object to XML compatible with .NET XmlSerializer.Deserialize 
     * @param type $object Object to serialize
     * @param type $root_node Root node name (if null, objects class name is used)
     * @return string XML string
     */
    public static function XSerialize($object, $root_node = null) {
        $xml = '<?xml version="1.0" encoding="UTF-8" ?>';
        if (!$root_node) {
            $root_node = self::GetClassNameWithoutNamespace($object);
        }
        $xml .= '<' . $root_node . ' xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">';
        $xml .= self::SerializeNode($object);
        $xml .= '</' . $root_node . '>';
        return $xml;
    }

    /**
     * Create XML node from object property
     * @param mixed $node Object property
     * @param string $parent_node_name Parent node name
     * @param bool $is_array_item Is this node an item of an array?
     * @return string XML node as string
     * @throws Exception
     */
    private static function SerializeNode($node, $parent_node_name = false, $is_array_item = false) {
        $xml = '';
        if (is_object($node)) {
            $vars = get_object_vars($node);
        } else if (is_array($node)) {
            $vars = $node;
        } else {
            throw new Exception('Coś poszło nie tak');
        }

        foreach ($vars as $k => $v) {
            if (is_object($v)) {
                $node_name = ($parent_node_name ? $parent_node_name : self::GetClassNameWithoutNamespace($v));
                if (!$is_array_item) {
                    $node_name = $k;
                }
                $xml .= '<' . $node_name . '>';
                $xml .= self::SerializeNode($v);
                $xml .= '</' . $node_name . '>';
            } else if (is_array($v)) {
                $xml .= '<' . $k . '>';
                if (count($v) > 0) {
                    if (is_object(reset($v))) {
                        $xml .= self::SerializeNode($v, self::GetClassNameWithoutNamespace(reset($v)), true);
                    } else {
                        $xml .= self::SerializeNode($v, gettype(reset($v)), true);
                    }
                } else {
                    $xml .= self::SerializeNode($v, false, true);
                }
                $xml .= '</' . $k . '>';
            } else {
                $node_name = ($parent_node_name ? $parent_node_name : $k);
                if ($v === null) {
                    continue;
                } else {
                    $xml .= '<' . $node_name . '>';
                    if (is_bool($v)) {
                        $xml .= $v ? 'true' : 'false';
                    } else {
                        $xml .= htmlspecialchars($v, ENT_QUOTES);
                    }
                    $xml .= '</' . $node_name . '>';
                }
            }
        }
        return $xml;
    }
}