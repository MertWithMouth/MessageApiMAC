<?php


require_once('db.php');
require_once('../model/Message.php');
require_once('../model/Response.php');

try{

    $writeDB=DB::connectWriteDB();
    $readDB=DB::connectReadDB();


}

catch(PDOException $ex){

    error_log("Connection error - ".$ex, 0);
    $response=new Response();
    $response->setSuccess(false);
    $response->setHttpStatusCode(500);
    $response->addMessage("Database Connection Error");
    $response->send();
    exit();

}


if(array_key_exists("messageid", $_GET)){


    $messageid=$_GET['messageid'];
    if($messageid ==''|| !is_numeric($messageid)){


        $response= new Response();
        $response->setSuccess(false);
        $response->setHttpStatusCode(400);
        $response->addMessage("Message ID cannot be blank or must be numeric ");
        $response->send();
        exit();
    }


    if($_SERVER['REQUEST_METHOD'] ==='GET'){

        try{

            $query=$readDB->prepare('select id, username, text from tblmessages where id= :messageid');
            $query->bindParam(':messageid', $messageid, PDO::PARAM_INT);
            $query->execute();

            $rowCount=$query->rowCount();


            if($rowCount ===0){

                $response= new Response();
                $response->setSuccess(false);
                $response->setHttpStatusCode(404);
                $response->addMessage("Message not found!");
                $response->send();
                exit();

            }


            while($row=$query->fetch(PDO::FETCH_ASSOC)){

                $message =new Message($row['id'], $row['username'], $row['text']);
                $messageArray[]=$message->returnMessageAsArray();


            }

            $returnData=array();
            $returnData['rows_returned'] =$rowCount;
            $returnData['messages']=$messageArray;



                $response= new Response();
                $response->setHttpStatusCode(200);
                $response->setSuccess(true);
                $response->toCache(true);
                $response->setData($returnData);
                $response->send();
                exit();

        }
        catch(MessageException $ex){
            
            $response= new Response();
            $response->setSuccess(false);
            $response->setHttpStatusCode(500);
             $response->addMessage($ex->getMessage());
             $response->send();
                exit();

        }



        catch(PDOException $ex){

            error_log("Database query error - ".$ex, 0);
            $response=new Response();
            $response->setSuccess(false);
            $response->setHttpStatusCode(500);
            $response->addMessage("Failed to get Task");
            $response->send();
            exit();


        }


    }


    elseif($_SERVER['REQUEST_METHOD'] ==='DELETE'){

        try{

            $query = $writeDB -> prepare('delete from tblmessages where id =:messageid');
            $query->bindParam(':messageid', $messageid, PDO::PARAM_INT);
            $query->execute();

            $rowCount= $query -> rowCount();

            if($rowCount ===0){

                $response= new Response();
                $response->setSuccess(false);
                $response->setHttpStatusCode(404);
                $response->addMessage("Task not found!");
                $response->send();
                exit();

            }


                $response= new Response();
                $response->setSuccess(true);
                $response->setHttpStatusCode(200);
                $response->addMessage("Task is deleted");
                $response->send();
                exit();


        }

        catch(PDOException $ex){
            $response= new Response();
            $response->setSuccess(false);
            $response->setHttpStatusCode(500);
            $response->addMessage("Failed to delete the task");
            $response->send();
            exit();




        }

    }

    elseif($_SERVER['REQUEST_METHOD'] ==='PATCH'){

    }

    //POST WON'T ASK ID cause it will be created
    else{


        $response= new Response();
        $response->setSuccess(false);
        $response->setHttpStatusCode(405);
        $response->addMessage("Request method is not allowed");
        $response->send();
        exit();

    }

    

}







elseif(empty($_GET)){

    if($_SERVER['REQUEST_METHOD'] ==='GET'){

        try{

            $query=$readDB->prepare('select id, username, text from tblmessages');
            $query->execute();

            $rowCount=$query->rowCount();

            $messageArray=array();

            while($row = $query ->fetch(PDO::FETCH_ASSOC)){

                $message =new Message($row['id'], $row['username'], $row['text']);
                $messageArray[]=$message->returnMessageAsArray();

            }

            $returnData=array();
            $returnData['rows_returned']=$rowCount;
            $returnData['messages']=$messageArray;

                $response= new Response();
                $response->setSuccess(true);
                $response->setHttpStatusCode(200);
                $response->toCache(true);
                $response->setData($returnData);
                $response->send();
                exit();

        }


        catch(MessageException $ex){

            $response = new Response();
            $response->setSuccess(false);
            $response->setHttpStatusCode(500);
            $response->addMessage($ex ->getMessage());
            $response->send();



        }

        catch(PDOException $ex){

            error_log("Database query error - ".$ex,0);
            $response = new Response();
            $response->setSuccess(false);
            $response->setHttpStatusCode(500);
            $response->addMessage($ex ->getMessage());
            $response->send();



        }



    }

    elseif($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    
    try {
     
      if($_SERVER['CONTENT_TYPE'] !== 'application/json') {
        
        $response = new Response();
        $response->setHttpStatusCode(400);
        $response->setSuccess(false);
        $response->addMessage("Content Type header not set to JSON");
        $response->send();
        exit;
      }
      
      $rawPostData = file_get_contents('php://input');
      
      if(!$jsonData = json_decode($rawPostData)) {
        $response = new Response();
        $response->setHttpStatusCode(400);
        $response->setSuccess(false);
        $response->addMessage("Request body is not valid JSON");
        $response->send();
        exit;
      }
      
      if(!isset($jsonData->username)) {
        $response = new Response();
        $response->setHttpStatusCode(400);
        $response->setSuccess(false);
        (!isset($jsonData->username) ? $response->addMessage("username field is mandatory and must be provided") : false);
        $response->send();
        exit;
      }
      
      $newmessage = new Message(null, $jsonData->username, (isset($jsonData->text) ? $jsonData->text : null));
      $username = $newmessage->getUsername();
      $text = $newmessage->getText();
      
      $query = $writeDB->prepare('insert into tblmessages (username, text) values (:username, :text)');
      $query->bindParam(':username', $username, PDO::PARAM_STR);
      $query->bindParam(':text', $text, PDO::PARAM_STR);
      $query->execute();
      
      $rowCount = $query->rowCount();

      if($rowCount === 0) {
        $response = new Response();
        $response->setHttpStatusCode(500);
        $response->setSuccess(false);
        $response->addMessage("Failed to create task");
        $response->send();
        exit;
      }
      
      $lastMessageID = $writeDB->lastInsertId();
      $query = $writeDB->prepare('select id, username, text from tblmessages where id = :messageid');
      $query->bindParam(':messageid', $lastMessageID, PDO::PARAM_INT);
      $query->execute();

      $rowCount = $query->rowCount();
      
      if($rowCount === 0) {
        $response = new Response();
        $response->setHttpStatusCode(500);
        $response->setSuccess(false);
        $response->addMessage("Failed to retrieve task after creation");
        $response->send();
        exit;
      }
      
      $messageArray = array();

      while($row = $query->fetch(PDO::FETCH_ASSOC)) {
        $message = new Message($row['id'], $row['username'], $row['text']);

        $messageArray[] = $message->returnMessageAsArray();
      }
      $returnData = array();
      $returnData['rows_returned'] = $rowCount;
      $returnData['messages'] = $messageArray;

      $response = new Response();
      $response->setHttpStatusCode(201);
      $response->setSuccess(true);
      $response->addMessage("Task created");
      $response->setData($returnData);
      $response->send();
      exit;      




        }

        catch(MessageException $ex){
            
            $response= new Response();
            $response->setSuccess(false);
            $response->setHttpStatusCode(400);
            $response->addMessage($ex->getMessage());
            $response->send();
            exit();
        
            }
        
        
        
            catch(PDOException $ex){
        
                error_log("Database query error - ".$ex, 0);
                $response=new Response();
                $response->setSuccess(false);
                $response->setHttpStatusCode(500);
                $response->addMessage($ex->getMessage());
                $response->send();
                exit();
        
        
            }
        





    }
    else {
        $response = new Response();
        $response->setHttpStatusCode(405);
        $response->setSuccess(false);
        $response->addMessage("Request method is not allowed");
        $response->send();
    }







}

else{
    $response = new Response();
    $response->setHttpStatusCode(404);
    $response->setSuccess(false);
    $response->addMessage("Endpoint not found");
    $response->send();
}



    



