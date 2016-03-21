<?php
namespace ExpertiseBundle\Tree;

use Tree\Node\Node;


abstract class ExpertiseTree extends Node
{
    protected $_node = null;
    public function __construct(Node $node)
    {
        $this ->_node = $node;
    }

    abstract public function getStatus();
    abstract public function getList();
    abstract public function createExpertise();
    abstract public function createProcess();
}