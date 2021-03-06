<?php
namespace BuilderBundle\Controller\Builder;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use BuilderBundle\Controller\Builder\Utils;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class BuildContentController extends Controller
{
    //Contents of default page with filtered position: selectposition*
    public function buildDefaultContentAction($selectposition, Request $request)
    {
        $page = $this->get('doctrine.orm.entity_manager')
            ->getRepository('BuilderBundle:Page')
            ->findOneBy(array('slug' => 'default'));
        $selectContents = null;
        if ($page) {
            $pageContents = $page->getPageContents();
            $selectContents = Utils::filterPageContentPositionStartWith($pageContents, $selectposition);
        }
        return $this->render('BuilderBundle:BuildPage:buildcontents.html.twig', array(
            'page' => $page,
            'contents' => $selectContents,
            'notfoundmessage' => 'Erreur: le contenu par défaut en position ' . $selectposition . ' doit être défini dans la base.'
        ));
        // foreach($selectContents as $sContent)
        // {
        //     return $this->buildContentAction($sContent, $request);
        // }
    }

    //ONLY CONTENT, peut être utilisé dans un popup par exemple
    public function buildPageContentAction($slug, Request $request)
    {
        //dump($slug);
        $page = $this->get('doctrine.orm.entity_manager')
            ->getRepository('BuilderBundle:Page')
            ->findOneBy(array('slug' => $slug));
        $pageContents = null;
        if ($page) {
            $pageContents = $page->getPageContents();
        }
        return $this->render('BuilderBundle:BuildPage:buildcontents.html.twig', array(
            'page' => $page,
            'contents' => $pageContents,
            'notfoundmessage' => 'Erreur: Pas de contenu trouvé.'
        ));
    }

    public function buildContentAction($pageContent, $id = 0, Request $request)
    {
        //Call correct controlleur depending type: $pageContent->getContent()->getType()
        switch ($pageContent->getContent()->getType()) {
            case "Image":
                return $this->render('BuilderBundle:BuildContent:image.html.twig', array(
                    'pageContent' => $pageContent
                ));
                break;
            case "MainMenu":
                return $this->render('BuilderBundle:BuildContent:menu.html.twig', array(
                    'pageContent' => $pageContent
                ));
                break;
            case "Content":
                return $this->render('BuilderBundle:BuildContent:content.html.twig', array(
                    'pageContent' => $pageContent
                ));
                break;
            case "Menu":
                return $this->menuAction($pageContent);
                break;
            case "Controller":
                //$this->generateUrl('my_login_path');
                return $this->forward(strip_tags($pageContent->getContent()->getContent()), ['request' => $request, 'id' => $id, 'pageContent' => $pageContent]);
                break;

            default: //Text
                return $this->render('BuilderBundle:BuildContent:text.html.twig', array(
                    'pageContent' => $pageContent
                ));
        }
    }

    //Appel du rendu d'un controlleur existant
    // controllerURL est une URL: on va chercher la route déclaré 
    public function buildWithControllerAction($controllerUrl, Request $request)
    {
        $message = "MESSAGE : "; //pour le debug
        $route = null;
        // 1er cas: gestion des contenus FosUserBunde avec prefix /user/
        //tentative de récupération de la route: /user/$controllerUrl
        $route = $this->testMatchUrl('/user/', $controllerUrl, $request);
        if (isset($route) && isset($route["_controller"])) {
            //return new JsonResponse($route);
            //$message = $message . " #route trouvé: /user/".$controllerUrl;
            return $this->forward($route['_controller'], ['request' => $request]);
        }
        $route = $this->testMatchUrl('/user/', $controllerUrl . '/', $request);
        if (isset($route)) {
            //return new JsonResponse($route);
            //$message = $message . " #route trouvé: /user/".$controllerUrl.'/';
            return $this->forward($route['_controller'], ['request' => $request]);
        }
        $route = $this->testMatchUrl('/content/', $controllerUrl, $request);
        if (isset($route)) {
            //return new JsonResponse($route);
            //$message = $message . " #route trouvé: /content/".$controllerUrl;
            return $this->forward($route['_controller'], ['request' => $request]);
        }
        $route = $this->testMatchUrl('/content/', $controllerUrl . '/', $request);
        if (isset($route)) {
            //$message = $message . " #route trouvé: /content/".$controllerUrl.'/';
            return $this->forward($route['_controller'], ['request' => $request]);
        }
        
        //return new Response($message);
        return new Response(" L'url demandé (" . $controllerUrl . ") n'est pas valide.");

        
        // //Si la route est une redirection, recurérer la route de la redirection
        // if(isset($route["path"]))
        // try{
        //     //$message = $message.'#try route : /user/+'.$controllerUrl;
        //     $route = $this->get('router')->match('/user/'.$controllerUrl);
        //     if(isset($route["path"]))
        //     {
        //         $message = $message.'#$route["path"]!= null => try redirect path :'.$route["path"];
        //         $route = $this->get('router')->match($route["path"]);
        //     }
        // }
        // catch(\Exception $e){
        //     try{
        //         $message = $message.' # and try route :/user/+'.$controllerUrl.'/';
        //         $route = $this->get('router')->match('/user/'.$controllerUrl.'/');
        //     }
        //     catch(\Exception $e2){
        //         $message = $message.'# no route match';
        //         $route = null;
        //     }
        // }
        // //return new Response($message);
        // //return new JsonResponse($route);
        // if($route != null && is_array($route))
        // {
        //     try{
        //         return $this->forward($route['_controller'], ['request' => $request]);
        //     }
        //     catch(\Exception $e){
        //         return new Response(' url demandé non valide: '.$controllerUrl);
        //     }
        // }
        // return new Response($message);
        // return new JsonResponse($route);
    }

    private function testMatchUrl($prefix, $url, $request)
    {
        try {
            $route = $this->get('router')->match($prefix . $url);
            //Si la route est une redirection, recurérer la route de la redirection
            if (isset($route["path"])) {
                $route = $this->get('router')->match($route["path"]);
            }
            //Si la route semble valide, appeller le controller
            if ($route != null && is_array($route)) {
                if (isset($route["_route"]) && $route["_route"] == "site_buildpage") {
                    return null;
                } else {
                    return $route;
                }
                //return $this->forward($route['_controller'], ['request' => $request]);
            }
        } catch (\Exception $e) {
            return null;
        }
    }

    // public function imageAction($pageContent, Request $request)
    // {
    //     return $this->render('BuilderBundle:BuildContent:image.html.twig', array(
    //         'pageContent' => $pageContent
    //     ));
    // }


    // public function textAction($pageContent, Request $request)
    // {
    //     return $this->render('BuilderBundle:BuildContent:text.html.twig', array(
    //         'pageContent' => $pageContent
    //     ));
    // }

    // public function menuAction($pageContent, Request $request)
    // {
    //     return $this->render('BuilderBundle:BuildContent:text.html.twig', array(
    //         'pageContent' => $pageContent
    //     ));
    // }

    public function menuAction($pageContent)
    {
        $menuName = $pageContent->getContent()->getContent();
        
        //IF menu is create with twig and not with knp_menu_render
        $menu = $this->get('doctrine.orm.entity_manager')
            ->getRepository('BuilderBundle:Menu')
            ->findOneBy(array('name' => strip_tags($menuName)));

        return $this->render('BuilderBundle:BuildContent:menu.html.twig', array(
            'menuName' => $menuName,
            'menu' => $menu,
            'pageContent' => $pageContent
        ));
    }

    public function menuPrincipalAction($menuName)
    {
        if ($menuName == null) {
            $menuName = 'Principal';
        }

        //LOAD MENU PRINCIPAL
        $menu = $this->get('doctrine.orm.entity_manager')
            ->getRepository('BuilderBundle:Menu')
            ->findOneBy(array('name' => $menuName));
        
        //initialisation
        $links = [];
        $i = 0;

        $user = $this->getUser();
        if ($menu != null) {
            foreach ($menu->getMenuPages() as $menuPage) {
            //VERIFIER LES DROITS D'ACCES A LA PAGE:
            // On vérifie que l'utilisateur dispose bien du rôle demandé au niveau de la page:
            // $hasPageRights = true si le group Users est indiqué
            // Sinon on controlle si le user a le group demandé
            // ou si l'utilisateur à le role 'ROLE_'+group.name dans toute les responsabilités calculées
                $hasPageRights = false;
                if (count($menuPage->getPage()->getRights()) == 0) {
                    $hasPageRights = true;
                }
                foreach ($menuPage->getPage()->getRights() as $group) {
                    if ($group->getName() == "All") {
                        $hasPageRights = true; //All users acce
                        break;
                    } elseif (isset($user) && ($user->hasGroup($group) || $this->get('security.authorization_checker')->isGranted(strtoupper('ROLE_' . $group->getName()))))// $user->hasRole(strtoupper('ROLE_'. $group->getName()))))
                    {
                        $hasPageRights = true; // users has rights
                        break;
                    }
                }

                if ($hasPageRights) {
                    $links[$i]["title"] = $menuPage->getPage()->getName();
                    $links[$i]["path"] = $menuPage->getPage()->getSlug();
                    $i = $i + 1;
                }
            }
        }

        return $this->render(':' . $this->container->getParameter('template_repo') . '/views/parts:main-menu.html.twig', array(
            'links' => $links,
        ));
    }

    public function menuFooterAction($menuName)
    {
        if ($menuName == null) {
            $menuName = 'Footer';
        }

        //LOAD MENU PRINCIPAL
        $menu = $this->get('doctrine.orm.entity_manager')
            ->getRepository('BuilderBundle:Menu')
            ->findOneBy(array('name' => $menuName));
        
        //initialisation
        $links = [];
        $i = 0;

        $user = $this->getUser();
        if ($menu != null) {
            foreach ($menu->getMenuPages() as $menuPage) {
            //VERIFIER LES DROITS D'ACCES A LA PAGE:
            // On vérifie que l'utilisateur dispose bien du rôle demandé au niveau de la page:
            // $hasPageRights = true si le group Users est indiqué
            // Sinon on controlle si le user a le group demandé
            // ou si l'utilisateur à le role 'ROLE_'+group.name dans toute les responsabilités calculées
                $hasPageRights = false;
                if (count($menuPage->getPage()->getRights()) == 0) {
                    $hasPageRights = true;
                }
                foreach ($menuPage->getPage()->getRights() as $group) {
                    if ($group->getName() == "All") {
                        $hasPageRights = true; //All users acce
                        break;
                    } elseif (isset($user) && ($user->hasGroup($group) || $this->get('security.authorization_checker')->isGranted(strtoupper('ROLE_' . $group->getName()))))// $user->hasRole(strtoupper('ROLE_'. $group->getName()))))
                    {
                        $hasPageRights = true; // users has rights
                        break;
                    }
                }

                if ($hasPageRights) {
                    $links[$i]["title"] = $menuPage->getPage()->getName();
                    $links[$i]["path"] = $menuPage->getPage()->getSlug();
                    $i = $i + 1;
                }
            }
        }

        return $this->render(':' . $this->container->getParameter('template_repo') . '/views/parts:footer-menu.html.twig', array(
            'links' => $links,
        ));
    }
}
