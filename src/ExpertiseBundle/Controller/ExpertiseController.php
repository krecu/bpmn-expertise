<?php

namespace ExpertiseBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use FOS\RestBundle\Controller\Annotations\View;

class ExpertiseController extends Controller
{

    /**
     * Получить статус.
     *
     * @ApiDoc(
     *  description="DВозвращает статус экспертизы по принципу 'снизу->вверх'. Тоесть определяеться текущая экспертиза и текущий пользователь по
     *  переданым данным, если пользователь не найден то и статус не будет получен, если же найден то рекурсивно по дереву проверяеться статус нижестоящих групп",
     *  requirements={
     *      {
     *          "name"="entityId",
     *          "dataType"="string",
     *          "description"="Entity ID"
     *      },
     *      {
     *          "name"="groupId",
     *          "dataType"="string",
     *          "description"="Group ID"
     *      },
     *      {
     *          "name"="userRole",
     *          "dataType"="string",
     *          "description"="role Machine Name"
     *      },
     *      {
     *          "name"="userId",
     *          "dataType"="string",
     *          "description"="user Id"
     *      },
     *      {
     *          "name"="entityType",
     *          "dataType"="string",
     *          "description"="entityType"
     *      }
     *  },
     *  tags={
     *    "stable"
     *  }
     * )
     * @View(serializerEnableMaxDepthChecks=true)
     */
    public function statusAction(Request $request) {
        $expertiseService = $this->container->get('app.expertise');
        $params = [];
        $params['groupId'] = $request->get('groupId');
        $params['entityId'] = $request->get('entityId');
        $params['userId'] = $request->get('userId');
        $params['entityType'] = $request->get('entityType');
        $params['userRole'] = $request->get('userRole');

        $result = $expertiseService->getStatusExpertise($params);
        return new JsonResponse($result, 200);
    }

    /**
     * Запустить процесс.
     *
     * @ApiDoc(
     *  description="Стартует экспертизу. Строит дерево групп и пользователей в группах создавая для каждого из них свой процесс. Вся остальная логика описанна в самом процессе. Сервис следит за логикой движения и передачи статуса а так же за форматы выдачи контролов",
     *  requirements={
     *      {
     *          "name"="entityId",
     *          "dataType"="string",
     *          "description"="Entity ID"
     *      },
     *      {
     *          "name"="groupId",
     *          "dataType"="string",
     *          "description"="Group ID"
     *      },
     *      {
     *          "name"="entityType",
     *          "dataType"="string",
     *          "description"="entityType"
     *      }
     *  },
     *  tags={
     *    "stable"
     *  }
     * )
     * @View(serializerEnableMaxDepthChecks=true)
     */
    public function startAction(Request $request) {

        $expertiseService = $this->container->get('app.expertise');
        $params = [];

        $params['groupId'] = $request->get('groupId'); //"f8fd4a4e-8c66-496d-94e3-a1b60a61706e";
        $params['userId'] = $request->get('userId'); //"f8fd4a4e-8c66-496d-94e3-a1b60a61706e";
        $params['entityId'] = $request->get('entityId'); //"f8fd4a4e-8c66-496d-94e3-a1b60a61706e";
        $params['entityType'] = $request->get('entityType'); //"f8fd4a4e-8c66-496d-94e3-a1b60a61706e";

        $result = $expertiseService->startExpertise($params);

        return new JsonResponse(json_encode($result), 200);
    }

    /**
     * Продвинуть процесс.
     *
     * @ApiDoc(
     *  description="Двигаем процесс - в качестве входных параметров необходимо использовать те что были переданы в статусе у контролов,
     * но можно и свои вставлять",
     *  requirements={
     *      {
     *          "name"="op",
     *          "dataType"="string",
     *          "description"="Operation in process"
     *      },
     *      {
     *          "name"="id",
     *          "dataType"="string",
     *          "description"="Expertise ID"
     *      },
     *  },
     *  tags={
     *    "stable"
     *  }
     * )
     *
     * @View(serializerEnableMaxDepthChecks=true)
     *
     */
    public function pushAction(Request $request) {

        $expertiseService = $this->container->get('app.expertise');
        $params = [];
        $params['op'] = $request->get('op'); //"f8fd4a4e-8c66-496d-94e3-a1b60a61706e";
        $params['id'] = $request->get('id'); //"f8fd4a4e-8c66-496d-94e3-a1b60a61706e";

        $result = $expertiseService->pushExpertise($params);

        return new JsonResponse(json_encode($result), 200);
    }

    public function getControlsAction(Request $request){
        $expertiseService = $this->container->get('app.expertise');
        $params['groupId'] = "96c43b82-c019-4db2-a3f1-94145d8bc01c";
        //$params['groupId'] = "f8fd4a4e-8c66-496d-94e3-a1b60a61706e";
        $params['userId'] = "f8fd4a4e-8c66-496d-94e3-a1b60a61706e";
        $params['entityId'] = "f8fd4a4e-8c66-496d-94e3-b1b60a61700e";
        $params['entityType'] = "plan";
        //$result = $expertiseService->startExpertise($params);
        $result = $expertiseService->getGroupExpertiseTree($params['groupId'], $params['entityId'], $params['userId'], "lot");
        dump($result->getUserById("cb4d36a8-a8dd-475b-b0c6-4d67bb4f70e8"));
        die;
        //        dump($result); die;
        return new JsonResponse(["ok"], 200);
    }

    public function testAction(Request $request) {

        $expertiseService = $this->container->get('app.expertise');
        $params['groupId'] = "a917e21a-c0c1-4d8e-af0d-3d230ec9e163";
        $params['entityId'] = "f2b897ac-155b-4d12-9ce8-5fda3f0c374b";
        $params['entityType'] = "object";
        $result = $expertiseService->getGroupExpertiseTree($params['groupId'], $params['entityId'], "object");

        dump($result);

        die;
//        dump($result); die;
        return new JsonResponse(["ok"], 200);
    }
}
