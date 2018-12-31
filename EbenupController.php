<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Post;
use App\Repository\PostRepository;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\ResetType;

class EbenupController extends AbstractController //dans le tuto youtube il a 'extends Controller'
{
    /**
     * @Route("/", name="ebenup_home")
     */
    public function index(PostRepository $repo)
    {
        $post = new Post();
        $form = $this->createFormBuilder($post)
              ->setAction($this->generateUrl('ebenup_welcome'))
              ->setMethod('GET')
              ->add('id', IntegerType::class,[
                'attr' => [
                    'placeholder' => "UserID",
                    'class' => 'form-control'
                ]
              ])
              ->getForm();

        return $this->render('ebenup/index.html.twig', [
            'controller_name' => 'EBENUP',
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/bienvenue", name="ebenup_welcome")
     */
    public function welcome(PostRepository $repo, Request $request)
    {
        $var=$request->query->get('form');
        $verifUserId = $repo->verifUserId($var['id']);
        if ($verifUserId == 0) {
            throw $this->createNotFoundException('The id does not exist');

            // the above is just a shortcut for:
            // throw new NotFoundHttpException('The product does not exist');
        }

        $var=$request->query->get('form');
        $post = $repo->findByUserId($var);
        return $this->render('ebenup/welcome.html.twig', [
            'infoSQL' => $post[0]
        ]);
    }



    /**
     * @Route("/chercheRes", name="rechercheRes") //le /rien dit que la fonction suivante redirige vers l'accueil du site. le nom de la route est home
     */
    public function rechercheRes(PostRepository $repo, Request $request) //barre de rcherche
    {
        $post = new Post();
        $form = $this->createFormBuilder($post)
              ->setAction($this->generateUrl('ebenup_reserver'))
              ->setMethod('GET')
              ->add('id', IntegerType::class,[
                'attr' => [
                    'placeholder' => "Numéro d'utilisateur",
                    'class' => 'form-control'
                ]
              ])
              ->getForm();

        return $this->render('ebenup/home.html.twig', [
            'title' => "Chercher une resrevation",
            'form' => $form->createView()
        ]);//permet d'appeler un fichier twig pour l'afficher
    }



    /**
     * @Route("/recherche", name="gererLaRecherche")
     * @param Request $request, PostRepository $repo
    */
    public function gererLaRecherche(PostRepository $repo, Request $request){ //infos res. apres la barre de recherche

        $var=$request->query->get('form');
        $post = $repo->findForMesRes($var);
        return $this->render('ebenup/recherche.html.twig', [
            'title' => 'Barre de Recherche',
            'infoSQL' => $post[0]
        ]);
    }



    /**
     * @Route("/ebenup/reservation", name="ebenup_reserver")
     */
    public function reserver(Request $request, ObjectManager $manager, PostRepository $repo)
    {

        $post = new Post();
        $form = $this->createFormBuilder($post)
            ->add('type', ChoiceType::class, [
                'choices' => [
                    'Votre choix' => null,
                    'Ebenpop' => 'Ebenpop',
                    'Ebenseat' => 'Ebenseat',
                    'Ebenlux' => 'Ebenlux'
                ],
                'attr' => ['class' => 'form-control']
            ])
            ->add('userId', IntegerType::class, [
                'attr' => [
                    'placeholder' => "Numéro d'utilisateur",
                    'class' => 'form-control',
                    'value' => $repo->recupId($request)
                ]
            ])
            ->add('date', DateType::class)
            ->add('prestation', ChoiceType::class, [
                'choices' => [
                    'FEMME' => [
                        'Coiffure' => 'coiffure',
                        'Maquillage' => 'maquillage',
                        'Epilation' => 'epilation'
                    ],
                    'HOMME' => [
                        'Barbe' => 'barbe',
                        'Coiffure' => 'coiffure_homme'
                    ]
                ],
                'attr' => ['class' => 'form-control']
            ])
            ->add('description', TextType::class, [
                'attr' => [
                    'placeholder' => "Description du votre demande",
                    'class' => 'form-control'
                ]
            ])
            ->add('confirmee', ChoiceType::class, [
                'choices' => [
                    'Confirmation' => [
                        'Valider la réservation maintenant' => 1,
                        'Placer dans le panier et valider plus tard' => 0
                    ]
                ],
                'attr' => ['class' => 'form-control']
            ])
            ->add('Effacer_les_champs', ResetType::class, array(
                'attr' => ['class' => 'form-control']
            ))
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) { //form soumis et valide
            $post->setDate(new \DateTime());
            $manager->persist($post);
            $manager->flush();

            $this->addFlash('success', 'Votre réservation a été effectué avec succès.');

            return $this->redirectToRoute('gererLaRecherche', ['form[id]'=> $repo->recupId($request)]); // apres le formulaire de reservation, on est rediririgés sur la page "mes reservation"
        }

        $var=$request->query->get('form');
        $res = $repo->findForMesRes($var);

        return $this->render('ebenup/reserver.html.twig', [
            'formPost' => $form->createView(),
            'get' => $var['id'],
            'infoSQL' => $res[0]
        ]);
    }


    /**
     * @Route("/ebenup/VotreReservation", name="afficherLaReservation")
     */
    public function afficherLaReservation(PostRepository $repo, Request $request)
    {
        $var=$request->query->get('form');
        $res = $repo->findForMesRes($var); //On pourrais enlever cette ligne grace a ParamConverter
        return $this->render('ebenup/afficherLaReservation.html.twig', [
            'infoSQL' => $res[0]
        ]);//permet d'appeler un fichier twig pour l'afficher

    }

    /**
     * @Route("/ebenup/panier", name="ebenup_panier")
     */
    public function panier(PostRepository $repo, Request $request)
    {
        $var=$request->query->get('form');
        $res = $repo->findForPanier($var); //On pourrais enlever cette ligne grace a ParamConverter

        return $this->render('ebenup/panier.html.twig', [
            'infoSQL' => $res[0]
        ]);//permet d'appeler un fichier twig pour l'afficher

    }
}


