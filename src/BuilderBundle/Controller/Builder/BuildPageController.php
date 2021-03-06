<?php
namespace BuilderBundle\Controller\Builder;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\Common\Collections\Criteria;
use BuilderBundle\Controller\Builder\Utils;

class BuildPageController extends Controller
{

    //C EST LE BUILDER PRINCIPAL DES PAGES DU SITE
    // CHARGE LE CONTENU DE LA PAGE:
    // 1: Charge le template twig s'il existe dans app/"%template_repo%"/views:'.$slug.'.html.twig'
    // 2: sinon si le slug correspond à une Page, charge la page avec le builder
    public function buildPageAction($slug, $id = 0, $tab = '', Request $request)
    {
        //dump($request);
        //$template_repo is repository of OPEN version : OPEN_241117
        $template_repo = $this->container->getParameter('template_repo');

        $em = $this->get('doctrine.orm.entity_manager');
        $page = $em->getRepository('BuilderBundle:Page')->findOneBy(array('slug' => $slug));

        $user = $this->getUser();
        $pageContents = [];
        $carousel = null;
            //VERIFIER LES DROITS D'ACCES A LA PAGE:
            // On vérifie que l'utilisateur dispose bien du rôle demandé au niveau de la page:
                // $hasPageRights = true si le group Users est indiqué
                // Sinon on controlle si le user a le group demandé
                // ou si l'utilisateur à le role 'ROLE_'+group.name dans toute les responsabilités calculées
        $hasPageRights = false;
        $imageHero_exists = true;

        $mainController = null;

        if ($page != null) { //CREATE PAGE CONTENT FROM DATABASE
                //VERIFICATION DES DROITS D'ACCES A LA PAGE
            foreach ($page->getRights() as $group) {
                if ($group->getName() == "All") {
                    $hasPageRights = true; //All users can access
                    break;
                } elseif (isset($user) && ($user->hasGroup($group) || $this->get('security.authorization_checker')->isGranted(strtoupper('ROLE_' . $group->getName()))))// $user->hasRole(strtoupper('ROLE_'. $group->getName()))))
                {
                    $hasPageRights = true; // users has rights
                    break;
                }
            }

                //VERIFICATION DES DROITS D'ACCES AUX CONTENTS - Calculé Coté TEMPLATE:
                // On fait les mêmes vérifications avec is_granted('ROLE_'+group.name)
                // Les droits du menu sont gérés dans le template du menu

            $pageContents = $page->getPageContents();

            //IF a $pageContent has position Main
            // if type is Controller use pcontent.content.content
            foreach ($pageContents as $pcontent) {
                //if($pcontent->getPosition() contains MAIN && $pcontent->getContent()->getType() == "Controller")
                if (strpos($pcontent->getPosition(), 'MAIN') !== false && $pcontent->getContent()->getType() == "Controller") {
                    $mainController = $pcontent->getContent()->getContent();
                }
            }

            //CHECK IMAGE HERO EXISTS: Visuels
            $filename = 'Visuel' . $page->getSlug() . '.jpg';
            $imageHero_exists = file_exists($this->get('kernel')->getProjectDir() . '/web/' . $this->container->getParameter('template_repo') . '/assets/images/' . $filename);
                
            //ADD CARROUSEL
            if ($page->getSlug() === "home") {
                $carousel = $em->getRepository('BuilderBundle:Carousel')->findAll();
            }

            // if pcontent.content is Controller => need to forward it:
            if ($mainController != null) {
                $builderparams = array(
                    'user' => $user,
                    'page' => $page,
                    'notallowed' => !$hasPageRights,
                    'pageContents' => $pageContents,
                    'imageHero_exists' => $imageHero_exists,
                    'carousel' => $carousel,

                );
                //dump($builderparams);
                //dump($id);
                return $this->forward(strip_tags($mainController), array(
                    'slug' => $slug,
                    'id' => $id,
                    'tab' => $tab,
                    'builderparams' => $builderparams,
                    'request' => $request,
                ));
            }
        }

        $pagetemplate = 'BuilderBundle:BuildPage:buildpage.html.twig';
        
        //if tempate exists in $template_repo use it?
        // if ($this->get('twig')->getLoader()->exists(':' . $template_repo . '/views:' . $slug . '.html.twig')) {
        //     $pagetemplate = ':' . $template_repo . '/views:' . $slug . '.html.twig';
        // }
        
        return $this->render($pagetemplate, array(
            'slug' => $slug,
            'id' => $id,
            'tab' => $tab,
            'user' => $user,
            'page' => $page,
            'notallowed' => !$hasPageRights,
            'pageContents' => $pageContents,
            'imageHero_exists' => $imageHero_exists,
            'carousel' => $carousel,
            'request' => $request
        ));
    }


    //Basique page pour admin.
    //URL à créer: /details/slug restreint au admin
    public function buildPageDetailsAction($slug, Request $request)
    {
        //$pageid = $request->get('pageid');

        $page = $this->get('doctrine.orm.entity_manager')
            ->getRepository('BuilderBundle:Page')
            ->findOneBy(array('slug' => $slug));

        $pageContents = $this->get('doctrine.orm.entity_manager')
            ->getRepository('BuilderBundle:Page_Content')
            ->findBy(array('page' => $page));

        return $this->render('BuilderBundle:BuildPage:pagedetails.html.twig', array(
            'page' => $page,
            'pageContents' => $pageContents
        ));
    }
    
    //OLD NOT USED
    public function buildPageWithTemplateAction($slug = "home", $id = 0, $tab = '', Request $request)
    {

        $page = $this->get('doctrine.orm.entity_manager')
            ->getRepository('BuilderBundle:Page')
            ->findOneBy(array('slug' => $slug));

        $user = $this->getUser();
        //VERIFIER LES DROITS D'ACCES A LA PAGE:
        // On vérifie que l'utilisateur dispose bien du rôle demandé au niveau de la page:
            // $hasPageRights = true si le group Users est indiqué
            // Sinon on controlle si le user a le group demandé
            // ou si l'utilisateur à le role 'ROLE_'+group.name dans toute les responsabilités calculées
        $hasPageRights = false;

        $pageContents = [];
        $headerContents = [];
        $menuContent = [];
        $pContents = [];
        $footerContents = [];
        $template = 'BuilderBundle:OLD:buildpagetemplated.html.twig';

        if ($page != null) {
            foreach ($page->getRights() as $group) {
                if ($group->getName() == "Users") {
                    $hasPageRights = true; //All users acce
                    break;
                } elseif (isset($user) && ($user->hasGroup($group) || $this->get('security.authorization_checker')->isGranted(strtoupper('ROLE_' . $group->getName()))))// $user->hasRole(strtoupper('ROLE_'. $group->getName()))))
                {
                    $hasPageRights = true; // users has rights
                    break;
                }
            }

            //VERIFICATION DES DROITS D'ACCES AUX CONTENTS - Coté TEMPLATE:
            // Calculé dans le template pour le header/content/footer
            // On fait les mêmes vérifications avec is_granted('ROLE_'+group.name)
            // Les droits du menu sont gérés dans le template du menu

            $pageContents = $page->getPageContents();

            //CREATION DES CONTENU DE LA PAGE:
            //LES MOTS CLEFS SONT DEFINIS DANS LA POSITION DES CONTENT DE LA PAGE:
            // "Header"+* s'ajoutera dans le Header de la page
            // "MainMenu" => le premier élément trouvé défini le nom du menu principal
            // "Content"+* s'ajoutera dans le bloc body de la page
            // "Footer"+* s'ajoutera dans le bloc body de la page
        
        

            //Filter pageContents: Header, MainMenu, Content, Footer

            $headerContents = Utils::filterPageContentPositionStartWith($pageContents, "Header");
            //IF headerContents is null: default Header

            $menuContent = Utils::filterOnePageContentPositionStartWith($pageContents, "MainMenu");
            //IF menuContent is null: default menu (Principal)

            //Filter pageContents position startwith
            $pContents = Utils::filterPageContentPositionStartWith($pageContents, "Content");

            //Filter pageContents position startwith
            $footerContents = Utils::filterPageContentPositionStartWith($pageContents, "Footer");
        
            // Possible de changer le template par defaut si un content de la page à la position:
            // "Template", son contenu devra être le nom complet du template souhaité
            $templateContent = Utils::filterOnePageContentPositionStartWith($pageContents, "Template");
            if ($templateContent != null)
                $template = strip_tags($templateContent->getContent()->getContent());

            // Evolution possible: pouvoir créer un page_content structure_page indiquant les mots clefs à utiliser main il faut un twig correspondant
        }
        return $this->render($template, array(
            'page' => $page,
            'pageContents' => $pageContents,
            'menuContent' => $menuContent,
            'headerContents' => $headerContents,
            'pContents' => $pContents,
            'footerContents' => $footerContents,
            'template' => $template,
            'user' => $user,
            'notallowed' => !$hasPageRights
        ));
    }

    
    // //OLD
    // //CREATION DU MENU PRINCIPAL (utilisé dans le template par defaut du 16_11_2017)
    public function menuPrincipalAction($menuName)
    {
        if ($menuName == null)
        {
            $menuName = 'Principal';
        }

        //IF menu is create with twig and not with knp_menu_render
        $menuprincipal = $this->get('doctrine.orm.entity_manager')
            ->getRepository('BuilderBundle:Menu')
            ->findOneBy(array('name' => strip_tags($menuName)));

        return $this->render('BuilderBundle:BuildContent:menu.html.twig', array(
            'menuName' => $menuName,
            'menuprincipal' => $menuprincipal
        ));
    }
}
