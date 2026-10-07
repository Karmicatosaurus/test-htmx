<?php

declare(strict_types=1);

class Image
{
    private const LARGEUR_MAX = 800;
    private const HAUTEUR_MAX = 600;

    private const LARGEUR_SOURCE_MAX = 8000;
    private const HAUTEUR_SOURCE_MAX = 8000;

    private const QUALITE_JPG = 85;
    private const QUALITE_WEBP = 85;
    private const COMPRESSION_PNG = 6;

    private const DOSSIER_DEPOT = '/charge';

    private const MIME_EXTENSIONS = [
        'image/jpeg'    => '.jpg',
        'image/png'     => '.png',
        'image/webp'    => '.webp',
        'image/gif'     => '.gif'
    ];

    private readonly string $nomFichierTemporaire;
    private readonly string $dossierDestination;
    private readonly string $dossierPublic;

    private string $nvNomFichier = '';
    private string $nvNomFichierPublic = '';
    private string $mime = '';

    private int $largeur = 0;
    private int $hauteur = 0;
    private int $nvLargeur = 0;
    private int $nvHauteur = 0;


    public function __construct(string $nomFichierTemporaire, ?string $dossierDestination = null, ?string $dossierPublic = null)
    {
        $this->nomFichierTemporaire = $nomFichierTemporaire;
        $this->dossierDestination = rtrim($dossierDestination ?? (__DIR__ . self::DOSSIER_DEPOT), '/') . '/';
        $this->dossierPublic = trim($dossierPublic ?? self::DOSSIER_DEPOT) . '/';
    }

    /**
     * Récupère les dimensions et le mime de l'image
     */
    private function retourneInfos(): void
    {
        $infos = @getimagesize($this->nomFichierTemporaire);

        if($infos === false) {
            throw new \RuntimeException('Fichier invalide : impossible de lire les informations de l\'image');
        }

        [$largeur, $hauteur] = $infos;
        $mime = $infos['mime'];

        if(!isset(self::MIME_EXTENSIONS[$mime])) {
            throw new \RuntimeException('Mime incorrect !');
        }

        if($largeur <= 0 || $hauteur <= 0) {
            throw new \RuntimeException('Dimensions de l\'image incorrect !');
        }

        if($largeur > self::LARGEUR_SOURCE_MAX || $hauteur > self::HAUTEUR_SOURCE_MAX) {
            throw new \RuntimeException('Image trop grande !');
        }

        $this->largeur = $largeur;
        $this->hauteur = $hauteur;
        $this->mime = $mime;
    }

    /**
     * Retourne l'extension de l'image selon son mime
     *
     * @return string
     */
    private function retourneExtension(): string
    {
        return self::MIME_EXTENSIONS[$this->mime] ?? throw new \RuntimeException('Mime incorrect !');
    }

    /**
     * Génère le nouveau nom (chemin complet) de fichier
     *
     * @return array
     */
    private function genereNvNomImage(): array
    {
        if(!is_dir($this->dossierDestination) && !mkdir($this->dossierDestination,0755, true) && !is_dir($this->dossierDestination)) {
            throw new \RuntimeException('Impossible de créer le dossier de destination !');
        }

        $nomFichier = bin2hex(random_bytes(8)) . $this->retourneExtension();
        
        return [
            $this->dossierDestination . $nomFichier,
            $this->dossierPublic . $nomFichier
        ];
    }

    /**
     * Calcule les nouvelles dimensions en conservant le ratio,
     * sans jamais agrandir une image plus petite que le maximum
     */
    private function redimensionneImage(): void
    {
        if($this->largeur > self::LARGEUR_MAX || $this->hauteur > self::HAUTEUR_MAX) {
            $ratio = min(self::LARGEUR_MAX / $this->largeur, self::HAUTEUR_MAX / $this->hauteur);

            $this->nvLargeur = max(1, (int) round($this->largeur * $ratio));
            $this->nvHauteur = max(1, (int) round($this->hauteur * $ratio));
        } else {
            $this->nvLargeur = $this->largeur;
            $this->nvHauteur = $this->hauteur;
        }
    }

    /**
     * Charge l'image source depuis le fichier temporaire selon son mime
     *
     * @return \GdImage
     */
    private function chargeImageSource(): \GdImage
    {
        $image = match($this->mime) {
            'image/jpeg'    => imagecreatefromjpeg($this->nomFichierTemporaire),
            'image/png'     => imagecreatefrompng($this->nomFichierTemporaire),
            'image/webp'    => imagecreatefromwebp($this->nomFichierTemporaire),
            'image/gif'     => imagecreatefromgif($this->nomFichierTemporaire),
            default         => throw new \RuntimeException('Mime incorrect !')
        };

        if($image === false) {
            throw new \RuntimeException('Impossible de décoder l\'image source !');
        }

        return $image;
    }

    /**
     * Crée la toile de destination, avec un fond transparent pour les formats qui le supportent
     *
     * @return \GdImage
     */
    private function creeImageDestination(): \GdImage
    {
        $nvImage = imagecreatetruecolor($this->nvLargeur,$this->nvHauteur);

        if($nvImage === false) {
            throw new \RuntimeException('Impossible de créer l\'image de destination !');
        }

        if(in_array($this->mime, ['image/png','image/webp','image/gif'], true)) {
            imagealphablending($nvImage, false);
            imagesavealpha($nvImage, true);
            $transparent = imagecolorallocatealpha($nvImage,0,0,0,127);
            imagefilledrectangle($nvImage,0,0,$this->nvLargeur,$this->nvHauteur,$transparent);
        }

        return $nvImage;
    }

    /**
     * Encode et enregistre l'imge de destination sur le disque
     *
     * @param \GdImage $nvImage
     */
    private function enregistreImage(\GdImage $nvImage): void
    {
        $enregistre = match($this->mime) {
            'image/jpeg'    => imagejpeg($nvImage,$this->nvNomFichier,self::QUALITE_JPG),
            'image/png'     => imagepng($nvImage,$this->nvNomFichier,self::COMPRESSION_PNG),
            'image/webp'    => imagewebp($nvImage, $this->nvNomFichier, self::QUALITE_WEBP),
            'image/gif'     => imagegif($nvImage, $this->nvNomFichier),
            default         => throw new \RuntimeException('Mime incorrect !')           
        };

        if($enregistre === false) {
            throw new \RuntimeException('Echec de l\'enregistrement de l\'image !');  
        }
    }

    /**
     * Traite et enregistre l'image envoyé
     *
     */
    public function envoiImage(): void
    {
        $this->retourneInfos();
        $this->redimensionneImage();
        [$this->nvNomFichier,$this->nvNomFichierPublic] = $this->genereNvNomImage();

        $source = $this->chargeImageSource();
        $nvImage = $this->creeImageDestination();

        imagecopyresampled($nvImage,$source,0,0,0,0,$this->nvLargeur,$this->nvHauteur,$this->largeur,$this->hauteur);
        $this->enregistreImage($nvImage);
    }

    /**
     * Génère la balise HTML pour l'affichage
     *
     * @return string
     */
    public function afficheImage(): string
    {
        if(empty($this->nvNomFichierPublic)) {
            throw new \RuntimeException('Nom de fichier incorrect !');
        }

        return sprintf(
            '<p><img src="%s" alt="image" style="display:block;margin-left:auto;margin-right:auto;"></p>',
            htmlspecialchars($this->nvNomFichierPublic, ENT_QUOTES, 'UTF-8')
        );
    }
}