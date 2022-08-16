<?php

namespace application\controllers;

use application\libs\Application;

class UserController extends Controller
{
  public function join()
  {
    switch (getMethod()) {
      case _POST:
        $json = getJson();
        //아이디 중복 체크
        if ($this->model->selUser($json)) {
          return [_RESULT => 2];
        } else {
          $json["pw"] = password_hash($json["pw"], PASSWORD_BCRYPT);
          $image_parts = explode(";base64,", $json["profile_img"]);
          $image_type_aux = explode("image/", $image_parts[0]);
          $image_type = $image_type_aux[1];
          $image_base64 = base64_decode($image_parts[1]);
          $fileNm = uniqid() . "." . $image_type;
          $json["profile_img"] = $fileNm;
          if($iuser = $this->model->insUser($json)){
            $dirPath = _IMG_PATH . "/profile/" . $iuser;
            $filePath = $dirPath . "/" . $fileNm;
            if(!is_dir($dirPath)) {
              mkdir($dirPath, 0777, true);
            }
            $result = file_put_contents($filePath, $image_base64);
            if($result){
              return [_RESULT => 1];
            }
          };
        }
        return [_RESULT => 0];
    }
  }

  public function login()
  {
    switch (getMethod()) {
      case _POST:
        $json = getJson();
        $dbUser = $this->model->selUser($json);
        if (!$dbUser || !password_verify($json["pw"], $dbUser->pw)) {
          return [_RESULT => 0];
        } else {
          $dbUser->pw = null;
          $dbUser->regdt = null;
          $this->flash(_LOGINUSER, $dbUser);
          return [_RESULT => $dbUser];
        }
    }
  }

  public function logout()
  {
    switch (getMethod()) {
      case _POST:
        $this->flash(_LOGINUSER);
        return [_RESULT => 1];
    }
  }

  public function myPage()
  {
    $urlPaths = getUrlPaths();
    $param = [
      "iuser" => intval($urlPaths[2]), // iuser 타인으로 바꾸기
      "itravel" => intval($urlPaths[2])
    ];


    $myPageHost = $this->model->myPageHost($param); // 함수 쓰는법
    $myPageTravelState = $this->model->myPageTravelState($param);
    $myPageTravelFav = $this->model->myPageTravelFav($param);
    $param["loginIuser"] = intval($urlPaths[3]);
    $guestTravel = $this->model->selGuestTravel($param);
    $selUserFav = $this->model->selUserFav($param);

    $data = [
      "myPageTravelFav" => $myPageTravelFav,
      "myPageHost" => $myPageHost,
      "myPageTravelState" => $myPageTravelState,
      "guestTravel" => $guestTravel,
      "selUserFav" => $selUserFav,
    ];
    return [_RESULT => $data];
    // return $this->model->myPage($param);
  }

  public function getCmt(){
    switch (getMethod()) {
      case _GET:
        $urlPaths = getUrlPaths();
        $param = [
          "iuser" => intval($urlPaths[2]),
        ];
        return [_RESULT => $this->model->myPageCmt($param)];
    }
  }

  public function insCmt(){
    switch (getMethod()) {
      case _POST:
        $json = getJson();
        $param = [
        "itravel" => $json["itravel"],
        "guest_iuser" => $json["guest_iuser"],
        "cmt" => $json["cmt"],
        ];
        return [_RESULT => $this->model->insMypageCmt($param)];
    }
  }

  public function myAccount()
  {
    $urlPaths = getUrlPaths();
    $param = [
      "iuser" => intval($urlPaths[2]) // iuser 타인으로 바꾸기
    ];

    $selUser = $this->model->selUser($param);

    $data = [
      "selUser" => $selUser,
    ];
    return [_RESULT => $data];
  }

  public function myAccountMod(){
    
    switch (getMethod()) {
      case _POST:
        $json = getJson();
        $param = [
          "profile_img" => $json["profile_img"],
          "email" => $json["email"],
          "nm" => $json["nm"],
          "nick" => $json["nick"],
          "gender" => $json["gender"],
          "birth" => $json["birth"],
          "tel" => $json["tel"],
          "cmt" => $json["cmt"],
          "iuser" => $json["iuser"],
        ];
        if(isset($json["pw"])){
          $json["pw"] = password_hash($json["pw"], PASSWORD_BCRYPT);
          $param["pw"] = $json["pw"];
        }
        return [_RESULT => $this->model->MyAccountMod($param)];
    }
  }

  public function profileImg(){
    switch (getMethod()) {
      case _PUT:
        $json = getJson();
        $iuser = $json["params"]["iuser"];
        $image_parts = explode(";base64,", $json["params"]["profileImg"]);
        $image_type_aux = explode("image/", $image_parts[0]);
        $image_type = $image_type_aux[1];
        $image_base64 = base64_decode($image_parts[1]);
        $dirPath = _IMG_PATH . "/profile/" . $iuser;
        if(!is_dir($dirPath)) {
          mkdir($dirPath, 0777, true);
        }
        $loginUser = getLoginUser();
        if($loginUser->profile_img){
          $savedImg = $dirPath . "/" . $loginUser->profile_img;
          if(file_exists($savedImg)){
            unlink($savedImg);
          }
        }
        $fileNm = uniqid() . "." . $image_type;
        $filePath = $dirPath . "/" . $fileNm;
        $result = file_put_contents($filePath, $image_base64);
        if($result){
          $param = [
            "profile_img" => $fileNm,
            "iuser" => $iuser
          ];
          if($this->model->updProfileImg($param)){
            $loginUser->profile_img = $fileNm;
            return [_RESULT => 1];
          }
        }
        break;
      case _DELETE:
        $loginUser = getLoginUser();
        if($loginUser){
          $path = _IMG_PATH . "/profile/" . getIuser() . "/" . $loginUser->profile_img;
          if(file_exists($path) && unlink($path)){
            $param = [ "iuser" => getIuser(), "del" => 1 ];
            if($this->model->updProfileImg($param)){
              $loginUser->profile_img = null;
              return [_RESULT => 1];
            }
          }
        }
        return [_RESULT => 0];
        break;
    }
  }

  public function selUser(){
    switch (getMethod()) {
      case _GET:
        $urlPaths = getUrlPaths();
        $iuser = $urlPaths[2];
        $param = [
          "iuser" => $iuser
        ];
        return [_RESULT => $this->model->selUser($param)];
    }
  }
}