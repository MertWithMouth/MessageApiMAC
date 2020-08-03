<?php 

class MessageException extends Exception{




}

class Message{


    private $_id;
    private $_username;
    private $_text;
    

    public function __construct($id, $username, $text){


        $this-> setID($id);
        $this-> setUsername($username);
        $this-> setText($text);
       
    }

public function getID(){


    return $this->_id;
}

public function getUsername(){


    return $this->username;
}

public function getText(){


    return $this->_text;
}





public function setID($id){


    if($id !==null && (!is_numeric($id) || $id <=0 || $id > 2147483647 || $this->_id !==null)){

        throw new MessageException("Task ID error");
    }

    $this->_id=$id;
}

public function setUsername($username){


    if(strlen($username) <0 || strlen($username) >20){

        throw new MessageException("Task Username error");
    }

    $this->username=$username;
}

public function setText($text){


    if(($text !==null) && (strlen($text) >16777215)){

        throw new MessageException("Task text error");
    }

    $this->_text=$text;
}






public function returnMessageAsArray(){

    $message=array();
    $message['id']=$this->getId();
    $message['username']=$this->getUsername();
    $message['text']=$this->getText();
    return $message;
}

}