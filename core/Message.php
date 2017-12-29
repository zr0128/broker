<?php
namespace core;

/**
 * Class Message
 * @package core
 */
class Message
{

    public $fromId;
    public $toId;
    public $message;

    public function __construct($fromId, $toId, $message) {
        $this->fromId = $fromId;
        $this->toId = $toId;
        $this->message = $message;
    }
}