<?php

/*
Software License Agreement (BSD License)

Copyright (c) 2008 Scott MacVicar
All rights reserved.

Redistribution and use in source and binary forms, with or without
modification, are permitted provided that the following conditions
are met:
1. Redistributions of source code must retain the above copyright
   notice, this list of conditions and the following disclaimer.
2. Redistributions in binary form must reproduce the above copyright
   notice, this list of conditions and the following disclaimer in the
   documentation and/or other materials provided with the distribution.
3. The name of the author may not be used to endorse or promote products
   derived from this software without specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE AUTHOR ``AS IS'' AND ANY EXPRESS OR
IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES
OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.
IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT,
INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT
NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
(INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF
THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*/

class EasyRecipePlusPlistParser {
    /** @var  EasyRecipePlusPListStack */
    private $stack;
    private $currentKey;
    /**
     * @var EasyRecipePlusPlistArray
     */
    private $root;
    private $data;

    /**
     * @param $content
     *
     * @return mixed
     */
    public function parse($content) {
        $this->reset();

        $parser = xml_parser_create('UTF-8');
        xml_set_object($parser, $this);
        xml_set_element_handler($parser, 'handleBeginElement', 'handleEndElement');
        xml_set_character_data_handler($parser, 'handleData');
        xml_parse($parser, $content);
        xml_parser_free($parser);

        return $this->root;
    }

    private function reset() {
        $this->stack = new EasyRecipePlusPListStack();
        $this->currentKey = null;
        $this->root = null;
    }

    /** @noinspection PhpUnusedPrivateMethodInspection
     * @param $parser
     * @param $name
     * @param $attributes
     */
    private function handleBeginElement(/** @noinspection PhpUnusedParameterInspection */
        $parser, $name, $attributes) {
        $this->data = array();
        $handler = 'begin_' . $name;
        if (method_exists($this, $handler)) {
            call_user_func(array($this, $handler), $attributes);
        }
    }

    /** @noinspection PhpUnusedPrivateMethodInspection
     * @param $parser
     * @param $name
     */
    private function handleEndElement(/** @noinspection PhpUnusedParameterInspection */
        $parser, $name) {
        $handler = 'end_' . $name;
        if (method_exists($this, $handler)) {
            call_user_func(array($this, $handler));
        }
    }

    /** @noinspection PhpUnusedPrivateMethodInspection
     * @param $parser
     * @param $data
     */
    private function handleData(/** @noinspection PhpUnusedParameterInspection */
        $parser, $data) {
        $this->data[] = $data;
    }

    private function addObject($value) {
        if ($this->currentKey != null) {
            $this->stack->top()->offsetSet($this->currentKey, $value);
            $this->currentKey = null;
        } else if ($this->stack->isEmpty()) {
            $this->root = $value;
        } else {
            $this->stack->top()->append($value);
        }
    }

    private function getData() {
        $data = implode('', $this->data);
        $this->data = array();
        return $data;
    }

    /** @noinspection PhpUnusedPrivateMethodInspection
     * @param $attrs
     */
    private function begin_dict(/** @noinspection PhpUnusedParameterInspection */
        $attrs) {
        $a = new EasyRecipePlusPlistDict();
        $this->addObject($a);
        $this->stack->push($a);
    }

    /** @noinspection PhpUnusedPrivateMethodInspection */
    private function end_dict() {
        $this->stack->pop();
    }

    /** @noinspection PhpUnusedPrivateMethodInspection */
    private function end_key() {
        $this->currentKey = $this->getData();
    }

    /** @noinspection PhpUnusedPrivateMethodInspection
     * @param $attrs
     */
    private function begin_array(/** @noinspection PhpUnusedParameterInspection */
        $attrs) {
        $a = new EasyRecipePlusPlistArray();
        $this->addObject($a);
        $this->stack->push($a);
    }

    /** @noinspection PhpUnusedPrivateMethodInspection */
    private function end_array() {
        $this->stack->pop();
    }

    /** @noinspection PhpUnusedPrivateMethodInspection */
    private function end_true() {
        $this->addObject(true);
    }

    /** @noinspection PhpUnusedPrivateMethodInspection */
    private function end_false() {
        $this->addObject(false);
    }

    /** @noinspection PhpUnusedPrivateMethodInspection */
    private function end_integer() {
        $this->addObject(intval($this->getData()));
    }

    /** @noinspection PhpUnusedPrivateMethodInspection */
    private function end_real() {
        $this->addObject(floatval($this->getData()));
    }

    /** @noinspection PhpUnusedPrivateMethodInspection */
    private function end_string() {
        $this->addObject($this->getData());
    }

    /** @noinspection PhpUnusedPrivateMethodInspection */
    private function end_data() {
        $this->addObject(base64_decode($this->getData()));
    }

    /** @noinspection PhpUnusedPrivateMethodInspection */
    private function end_date() {
//        $this->addObject(new Date($this->getData()));
    }
}

class EasyRecipePlusPListStack {
    private $stack = array();

    public function pop() {
        return array_pop($this->stack);
    }

    public function push($data) {
        array_push($this->stack, $data);
        return true;
    }

    public function top() {
        return end($this->stack);
    }

    public function count() {
        return count($this->stack);
    }

    public function isEmpty() {
        return ($this->count() == 0);
    }
}

class EasyRecipePlusPlistDict extends ArrayObject {
    /**
     * Gets an item or returns a blank if the items doesn't exist
     *
     * @param $key
     *
     * @return string
     */
    function getString($key) {
        return isset($this[$key]) ? $this[$key] : '';
    }

    /**
     * Gets an array or returns an empty array if the item doesn't exist
     *
     * @param $key
     *
     * @return array
     */
    function getArray($key) {
        return isset($this[$key]) ? $this[$key] : array();
    }

}

class EasyRecipePlusPlistArray extends ArrayObject {
}

