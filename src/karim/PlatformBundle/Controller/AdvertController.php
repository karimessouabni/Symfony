<?php
/**
 * Created by PhpStorm.
 * User: karim
 * Date: 06/07/2016
 * Time: 09:05
 */


namespace karim\PlatformBundle\Controller;

use karim\PlatformBundle\Entity\Advert;
use karim\PlatformBundle\Entity\Image;
use karim\PlatformBundle\Entity\Application;
use karim\PlatformBundle\Entity\AdvertSkill;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;


class AdvertController extends Controller
{
    public function indexAction($page)
    {

        $mailer = $this->get('mailer');

        // On ne sait pas combien de pages il y a
        // Mais on sait qu'une page doit être supérieure ou égale à 1
        if ($page < 1) {
            // On déclenche une exception NotFoundHttpException, cela va afficher
            // une page d'erreur 404 (qu'on pourra personnaliser plus tard d'ailleurs)
            throw new NotFoundHttpException('Page "' . $page . '" inexistante.');
        }

        // Ici, on récupérera la liste des annonces, puis on la passera au template

        // Mais pour l'instant, on ne fait qu'appeler le template

        // Notre liste d'annonce en dur
        $listAdverts = array(
            array(
                'title' => 'Recherche développpeur Symfony',
                'id' => 1,
                'author' => 'Alexandre',
                'content' => 'Nous recherchons un développeur Symfony débutant sur Lyon. Blabla…',
                'date' => new \Datetime()),
            array(
                'title' => 'Mission de webmaster',
                'id' => 2,
                'author' => 'Hugo',
                'content' => 'Nous recherchons un webmaster capable de maintenir notre site internet. Blabla…',
                'date' => new \Datetime()),
            array(
                'title' => 'Offre de stage webdesigner',
                'id' => 3,
                'author' => 'Mathieu',
                'content' => 'Nous proposons un poste pour webdesigner. Blabla…',
                'date' => new \Datetime())
        );


        return $this->render('karimPlatformBundle:Advert:index.html.twig', array(
            'listAdverts' => $listAdverts
        ));
    }


    public function viewAction($id)
    {

        $id = 41;

        $em = $this->getDoctrine()->getManager();
        $advert = $em->getRepository('karimPlatformBundle:Advert')->find($id);


        if (null === $advert) {
            throw new NotFoundHttpException("L'annonce d'id " . $id . " n'existe pas.");
        }

        $listApplications = $em->getRepository('karimPlatformBundle:Application')->findBy(array('advert' => $advert));

        // On récupère maintenant la liste des AdvertSkill
        $listAdvertSkills = $em
            ->getRepository('karimPlatformBundle:AdvertSkill')
            ->findBy(array('advert' => $advert))
        ;

        // Le render ne change pas, on passait avant un tableau, maintenant un objet
        return $this->render('karimPlatformBundle:Advert:view.html.twig', array(
            'advert' => $advert,
            'listApplications' => $listApplications,
            'listAdvertSkills' => $listAdvertSkills
        ));
    }


    // On récupère tous les paramètres en arguments de la méthode
    public function viewSlugAction($slug, $year, $_format)
    {
        return new Response("On pourrait afficher l'annonce correspondant au slug '" . $slug . "', créée en " . $year . " et au format " . $_format . ".");
    }


    // Ajoutez cette méthode :
    public function addAction(Request $request)
    {

        $advert = new Advert();
        $advert->setTitle('Recherche développeur Symfony.');
        $advert->setAuthor('Alexandre');
        $advert->setContent("Nous recherchons un développeur Symfony débutant sur Lyon. Blabla…");

        $image = new Image();
        $image->setUrl("http://www.infopresse.com/Uploads/_cropped/Ville-branchee_1433188589.jpg");
        $image->setAlt('ma premiere image');

        $advert->setImage($image);


        // Création d'une première candidature
        $application1 = new Application();
        $application1->setAuthor('Marine');
        $application1->setContent("J'ai toutes les qualités requises.");

        // Création d'une deuxième candidature par exemple
        $application2 = new Application();
        $application2->setAuthor('Pierre');
        $application2->setContent("Je suis très motivé.");

        // On lie les candidatures à l'annonce
        $application1->setAdvert($advert);
        $application2->setAdvert($advert);


        // On récupère l'EntityManager
        $em = $this->getDoctrine()->getManager();

        // On récupère toutes les compétences possibles
        $listSkills = $em->getRepository('karimPlatformBundle:Skill')->findAll();

        // Pour chaque compétence
        foreach ($listSkills as $skill) {
            // On crée une nouvelle « relation entre 1 annonce et 1 compétence »
            $advertSkill = new AdvertSkill();

            // On la lie à l'annonce, qui est ici toujours la même
            $advertSkill->setAdvert($advert);
            // On la lie à la compétence, qui change ici dans la boucle foreach
            $advertSkill->setSkill($skill);

            // Arbitrairement, on dit que chaque compétence est requise au niveau 'Expert'
            $advertSkill->setLevel('Expert');

            // Et bien sûr, on persiste cette entité de relation, propriétaire des deux autres relations
            $em->persist($advertSkill);
        }

        // Doctrine ne connait pas encore l'entité $advert. Si vous n'avez pas défini la relation AdvertSkill
        // avec un cascade persist (ce qui est le cas si vous avez utilisé mon code), alors on doit persister $advert





        // Étape 1 : On « persiste » l'entité
        $em->persist($advert);


        //$em->getRepository('karimPlatformBundle:Advert')->find(2)->setDate(new \Datetime());
        $em->persist($application1);
        $em->persist($application2);


        // Étape 2 : On « flush » tout ce qui a été persisté avant
        $em->flush();


        // La gestion d'un formulaire est particulière, mais l'idée est la suivante :

        // Si la requête est en POST, c'est que le visiteur a soumis le formulaire
        if ($request->isMethod('POST')) {
            // Ici, on s'occupera de la création et de la gestion du formulaire

            $request->getSession()->getFlashBag()->add('notice', 'Annonce bien enregistrée.');

            // Puis on redirige vers la page de visualisation de cettte annonce
            return $this->redirectToRoute('karim_platform_view', array('id' => $advert->getId()));
        }

        // Si on n'est pas en POST, alors on affiche le formulaire
//        return $this->render('karimPlatformBundle:Advert:view.html.twig', array(
//            'advert' => $advert
//        ));

        return $this->redirectToRoute('karim_platform_home');
    }


    public function editAction($id, Request $request)
    {


        $em = $this->getDoctrine()->getManager();

        // On récupère l'annonce $id
        $advert = $em->getRepository('karimPlatformBundle:Advert')->find($id);

        if (null === $advert) {
            throw new NotFoundHttpException("L'annonce d'id ".$id." n'existe pas.");
        }

        // La méthode findAll retourne toutes les catégories de la base de données
        $listCategories = $em->getRepository('karimPlatformBundle:Category')->findAll();

        // On boucle sur les catégories pour les lier à l'annonce
        foreach ($listCategories as $category) {
            $advert->addCategory($category);
        }

        // Pour persister le changement dans la relation, il faut persister l'entité propriétaire
        // Ici, Advert est le propriétaire, donc inutile de la persister car on l'a récupérée depuis Doctrine

        // Étape 2 : On déclenche l'enregistrement
        $em->flush();


        return $this->render('karimPlatformBundle:Advert:edit.html.twig', array(
            'advert' => $advert
        ));

    }

    public function deleteAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $advert = $em->getRepository('karimPlatformBundle:Advert')->find($id);
        // Ici, on gérera la suppression de l'annonce en question

        if (null === $advert) {
            throw new NotFoundHttpException("L'annonce d'id ".$id." n'existe pas.");
        }

        // On boucle sur les catégories de l'annonce pour les supprimer
        foreach ($advert->getCategories() as $category) {
            $advert->removeCategory($category);
        }

        // Pour persister le changement dans la relation, il faut persister l'entité propriétaire
        // Ici, Advert est le propriétaire, donc inutile de la persister car on l'a récupérée depuis Doctrine

        // On déclenche la modification
        $em->flush();



        return $this->redirectToRoute('karim_platform_home');
    }


    public function menuAction()
    {
        // On fixe en dur une liste ici, bien entendu par la suite
        // on la récupérera depuis la BDD !
        $listAdverts = array(
            array('id' => 2, 'title' => 'Recherche développeur LSymfony'),
            array('id' => 5, 'title' => 'Mission de webmaster'),
            array('id' => 9, 'title' => 'Offre de stage webdesigner'),
            array('id' => 12, 'title' => 'derniere page')
        );

        return $this->render('karimPlatformBundle:Advert:menu.html.twig', array(
            // Tout l'intérêt est ici : le contrôleur passe
            // les variables nécessaires au template !
            'listAdverts' => $listAdverts
        ));
    }

}


