<?php

namespace ShonM\ResqueBundle\Twig;

class ResqueExtension extends \Twig_Extension
{
    public function getFilters()
    {
        return array(
            'json_pretty' => new \Twig_Filter_Method($this, 'jsonPretty', array('is_safe' => array('html'))),
            'iso8601' => new \Twig_Filter_Method($this, 'iso8601'),
        );
    }

    public function jsonPretty($json, $html = false)
    {
        $out = '';
        $nl = "\n";
        $cnt = 0;
        $tab = 4;
        $len = strlen($json);
        $space = ' ';

        if ($html) {
            $space = '&nbsp;';
            $nl = '<br/>';
        }

        $k = strlen($space) ? strlen($space) : 1;
        for ($i = 0; $i <= $len; $i++) {
            $char = substr($json, $i, 1);
            if ($char == '}' || $char == ']') {
                $cnt --;
                $out .= $nl . str_pad('', ($tab * $cnt * $k), $space);
            } elseif ($char == '{' || $char == '[') {
                $cnt ++;
            }
            $out .= $char;
            if ($char == ',' || $char == '{' || $char == '[') {
                $out .= $nl . str_pad('', ($tab * $cnt * $k), $space);
            }
            if ($char == ':') {
                $out .= ' ';
            }
        }

        return $out;
    }

    public function iso8601($stamp)
    {
        $datetime = new \DateTime($stamp);

        return $datetime->format(\DateTime::ISO8601);
    }

    public function getName()
    {
        return 'resque_extension';
    }
}
