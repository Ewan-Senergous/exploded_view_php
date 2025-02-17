<?php
if (!function_exists('afficherCaracteristiquesProduitV2')) {
    // Définition des constantes HTML
    define('HTML_STRONG_OPEN', '<strong>');
    define('HTML_STRONG_CLOSE', '</strong>');

    // Définition de l'exception dédiée
    class ProductNotFoundException extends Exception {
        public function __construct($message = "Produit non trouvé", $code = 0, Exception $previous = null) {
            parent::__construct($message, $code, $previous);
        }
    }

    function getProductVariationIdBySku($sku) {
        return ($id = wc_get_product_id_by_sku($sku)) ? $id : 0;
    }

    // Nouvelle fonction pour valider la position
    function isValidPosition($position) {
        // Convertir en entier et vérifier si c'est un nombre entre 1 et 1000
        $pos = intval($position);
        return $pos >= 1 && $pos <= 1000;
    }

    // Modifier la fonction pour ne vérifier que si le champ est vide
    function isEmptyOrSpecialChar($value) {
        return empty(trim($value)) || trim($value) === ' ';
    }

    function afficherCaracteristiquesProduitV2() {
        try {
            $product = wc_get_product(get_the_ID()) ?? $GLOBALS['product'] ?? throw new ProductNotFoundException();

            $cross_ref = '';
            foreach ($product->get_attributes() as $attr) {
                if (is_object($attr) && wc_attribute_label($attr->get_name()) === 'cross_ref') {
                    $cross_ref = implode(', ', $attr->get_options());
                    break;
                }
            }

            $jsonData = json_decode(preg_replace('/\s+/', ' ', $cross_ref), true);

            $output = '<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
            <style>
                * {
                    font-family: "Poppins", sans-serif;
                    font-size: 18px;
                }
                .add-to-cart-btn{background:#FF5733;transition:background-color .3s}
                .add-to-cart-btn:hover{background:#FF774D!important}
                .accordion {
                    margin-bottom: 15px;
                    border-radius: 8px;
                    overflow: hidden;
                    position: relative;
                }
                .accordion-header {
                    background: #0056B3;
                    color: white;
                    padding: 15px 20px;
                    cursor: pointer;
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    font-size: 18px;
                }
                .position-text {
                    font-weight: 600;
                    font-size: 18px;
                }
                .product-name {
                    font-weight: 600;
                    font-size: 18px;
                }
                .accordion-content {
                    display: none;
                    padding: 25px;
                    background: white;
                    border: 1px solid #e2e8f0;
                    font-size: 18px;
                }
                .accordion-content.active {
                    display: block;
                }
                .scroll-container {
                    max-height: 600px;
                    overflow-y: auto;
                }
                .zoom-controls.desktop {
                    display: block;
                }
                @media (max-width: 768px) {
                    .zoom-controls.desktop {
                        display: none !important;
                    }
                }
                @media (max-width: 768px) {
                    .scroll-container {
                        max-height: 400px !important;
                    }
                }
                .product-info-row {
                    display: flex;
                    margin: 0;
                }
                .details-dropdown {
                    margin: 0;
                }
                .actions-group {
                    display: flex;
                    flex-direction: column;
                    gap: 20px;
                }
                @media (min-width: 1900px) {
                    .product-content-container {
                        gap: 25px;
                    }
                    .actions-group {
                        flex-direction: row;
                         gap: 10px;
                    }
                }
                .product-content-container {
                    display: flex;
                    flex-direction: column;
                    gap: 20px;
                }
                .accordion-content > div {
                    display: flex;
                    flex-direction: column;
                    gap: 20px;
                }
                @media (min-width: 1900px) {
                    .product-info-row {
                        flex-wrap: wrap;
                        
                    }
                    .product-info-row span {
                        word-break: break-word;
                    }
                       
                }
                @media (max-width: 1900px) {
                    .product-info-row {
                        flex-direction: column;
                    }
                    .product-info-row span {
                        min-width: 100%;
                    }
                    .actions-container {
                        flex-direction: column;
                        align-items: stretch;
                        gap: 15px;
                    }
                    .scroll-container {
                        max-height: 500px;
                    }
                }
                .details-dropdown {
                    border: 1px solid #e2e8f0;
                    border-radius: 5px;
                }
                .dropdown-header {
                    background: #f8fafc;
                    padding: 10px 15px;
                    cursor: pointer;
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    border-radius: 5px;
                }
                .dropdown-content {
                    display: none;
                    padding: 15px;
                }
                .dropdown-content.show {
                    display: block;
                }
            </style>';

            // Dans votre tableau.php
            if (isset($jsonData['table_data'])) {
                $output .= '<div id="scroll-container" class="scroll-container">';
                
                // Filtrer et trier les données
                $filtered_data = array_filter($jsonData['table_data'], function($piece) {
                    return isset($piece['position_vue_eclatee']) &&
                           isValidPosition($piece['position_vue_eclatee']);
                });

                // Trier par position
                usort($filtered_data, function($a, $b) {
                    return intval($a['position_vue_eclatee']) - intval($b['position_vue_eclatee']);
                });

                foreach ($filtered_data as $index => $piece) {
                    $sku = htmlspecialchars($piece['reference_piece']);
                    $nom_piece = htmlspecialchars($piece['nom_piece']);
                    $variation_id = getProductVariationIdBySku($sku);
                    $position = intval($piece['position_vue_eclatee']);

                    $output .= sprintf('
                    <div class="accordion">
                        <div class="accordion-header" onclick="toggleAccordion(%d, event)">
                            <span>Position %d - <span class="product-name">%s</span></span>
                            <span class="arrow">▼</span>
                        </div>
                        <div id="accordion-%d" class="accordion-content %s">
                            <div class="product-content-container">',
                    $index,
                    $position,
                    HTML_STRONG_OPEN . $nom_piece . HTML_STRONG_CLOSE,
                    $index,
                    $index === 0 ? 'active' : ''
                );

                    // Remplacer la section qui affiche la référence pièce par la quantité
                    $output .= implode('', array_map(function($k, $v) {
                        
                        // N'afficher que la quantité en dehors de la dropdown
                        if ($k === 'quantite') {
                            $value = isEmptyOrSpecialChar($v)
                                ? '<span style="color: red; font-weight: bold;">VALEUR N\'EXISTE PAS</span>'
                                : HTML_STRONG_OPEN . htmlspecialchars($v) . HTML_STRONG_CLOSE;

                            return sprintf(
                                '<div class="product-info-row" style="display:flex;">
                                    <span style="color:#2c5282">Quantité&nbsp;:&nbsp;</span>
                                    <span>%s</span>
                                </div>',
                                $value
                            );
                        }
                        return '';
                    }, array_keys($piece), $piece));

                    // Ajouter la dropdown list avec toutes les autres informations
                    $output .= '
                    <div class="details-dropdown">
                        <div class="dropdown-header" onclick="toggleDropdown(this)">
                            <span>Détails supplémentaires</span>
                            <span class="dropdown-arrow">▼</span>
                        </div>
                        <div class="dropdown-content">';

                    // Définir l'ordre spécifique des champs
                    $orderedFields = ['nom_model', 'reference_model', 'reference_piece', 'contenu_dans_kit'];
                    foreach ($orderedFields as $field) {
                        if (isset($piece[$field])) {
                            $label = [
                                'nom_model' => 'Nom du modèle',
                                'reference_model' => 'Référence modèle',
                                'reference_piece' => 'Référence pièce',
                                'nom_piece' => 'Nom de la pièce',
                                'contenu_dans_kit' => 'Contenu dans le kit'
                            ][$field];
                            
                            $value = isEmptyOrSpecialChar($piece[$field])
                                ? '<span style="color: red; font-weight: bold;">VALEUR N\'EXISTE PAS</span>'
                                : HTML_STRONG_OPEN . htmlspecialchars($piece[$field]) . HTML_STRONG_CLOSE;
                            
                            $output .= sprintf(
                                '<div class="product-info-row">
                                    <span style="color:#2c5282">%s&nbsp;:&nbsp;</span>
                                    <span>%s</span>
                                </div>',
                                $label,
                                $value
                            );
                        }
                    }

                    $output .= '</div></div>';

                    // Groupe des actions (quantité et bouton panier)
                    $output .= '<div class="actions-group">';
                    $output .= sprintf('
                                    <div class="quantity-container" style="display:flex;align-items:center;gap:10px">
                                        <label style="color:#2c5282">Quantité :</label>
                                        <div style="display:flex;align-items:center">
                                            <button onclick="this.nextElementSibling.stepDown()" style="background:#f7fafc;border:1px solid #e2e8f0;padding:5px 9px;cursor:pointer">-</button>
                                            <input type="number" value="1" min="1" style="width:50px;text-align:center;border-top:1px solid #e2e8f0;border-bottom:1px solid #e2e8f0;border-left:none;border-right:none;padding:7px 0">
                                            <button onclick="this.previousElementSibling.stepUp()" style="background:#f7fafc;border:1px solid #e2e8f0;padding:5px 9px;cursor:pointer">+</button>
                                        </div>
                                    </div>
                                    <button onclick="ajouterAuPanier(\'%s\',%d)" class="add-to-cart-btn" style="color:white;padding:6px 9px;border:none;border-radius:5px;cursor:pointer;font-weight:bold;display:flex;align-items:center;gap:8px; width: fit-content">
                                        <svg width="16" height="16" viewBox="0 0 24 24" style="stroke:currentColor;fill:none;stroke-width:2"><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/></svg>
                                        Ajouter au panier
                                    </button>
                                </div>
                                <div id="alert-%d" style="display:none;margin-top:10px;padding:15px;border-radius:5px;background-color:#4CAF50;color:white;text-align:center">
                                    <div style="display:flex;justify-content:space-between;align-items:center">
                                        <span>✓ Produit ajouté au panier avec succès !</span>
                                        <a href="%s" style="background-color:white;color:#4CAF50;padding:8px 15px;border-radius:4px;text-decoration:none;font-weight:bold;transition:all .3s">Voir le panier</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>',
                    $sku,
                    $variation_id,
                    $variation_id,
                    wc_get_cart_url()
                    );
                }
                $output .= '</div>'; // fin de scroll-container

                // Ajouter les contrôles de zoom desktop
                $output .= '
                <div class="zoom-controls desktop">
                    <button class="zoom-button" onclick="resetZoom()">Reset</button>
                    <button class="zoom-button" onclick="zoomIn()">-</button>
                    <span id="zoomLevel" style="color: white; margin: 0 10px;">100%</span>
                    <button class="zoom-button" onclick="zoomOut()">+</button>
                </div>
                ';
            }

            $output .= '<script>
            function toggleAccordion(index, event) {
                event.preventDefault();

                const content = document.getElementById(`accordion-${index}`);
                const allContents = document.getElementsByClassName("accordion-content");
                const allHeaders = document.getElementsByClassName("accordion-header");
                const currentHeader = event.currentTarget;

                // Fermer tous les autres accordéons
                for(let i = 0; i < allContents.length; i++) {
                    const currentContent = allContents[i];
                    if(currentContent.id !== `accordion-${index}`) {
                        currentContent.style.display = "none";
                        currentContent.classList.remove("active");
                        allHeaders[i].querySelector(".arrow").innerHTML = "▼";
                    }
                }

                // Ouvrir/fermer l\'accordéon cliqué
                if(content.classList.contains("active")) {
                    content.style.display = "none";
                    content.classList.remove("active");
                    currentHeader.querySelector(".arrow").innerHTML = "▼";
                } else {
                    content.style.display = "block";
                    content.classList.add("active");
                    currentHeader.querySelector(".arrow").innerHTML = "▲";
                }
            }

            async function ajouterAuPanier(reference, productId) {
                const btn = event.currentTarget;
                const qty = btn.parentElement.querySelector("input[type=number]").value;

                try {
                    const formData = new FormData();
                    formData.append("action", "woocommerce_ajax_add_to_cart");
                    formData.append("product_id", productId);
                    formData.append("quantity", qty);
                    formData.append("add-to-cart", productId);

                    let ajaxUrl = "/wp-admin/admin-ajax.php";
                    if (typeof wc_add_to_cart_params !== "undefined") {
                        ajaxUrl = wc_add_to_cart_params.wc_ajax_url.toString().replace("%%endpoint%%", "add_to_cart");
                    }

                    const response = await fetch(ajaxUrl, {
                        method: "POST",
                        body: formData,
                        credentials: "same-origin"
                    });

                    const data = await response.json();
                    if (data.error) throw new Error(data.error);

                    btn.innerHTML = "<div style=\'display:flex;gap:8px;align-items:center;\'><svg width=\'16\' height=\'16\' viewBox=\'0 0 24 24\' style=\'stroke:currentColor;fill:none;stroke-width:2;\'><path d=\'M20 6L9 17l-5-5\'/></svg>Ajouté !</div>";

                    const alertElement = document.getElementById(`alert-${productId}`);
                    if (alertElement) alertElement.style.display = "block";

                    if (data.fragments) {
                        jQuery.each(data.fragments, function(key, value) {
                            jQuery(key).replaceWith(value);
                        });
                    }

                    jQuery(document.body).trigger("wc_fragments_refreshed");

                } catch (error) {
                    btn.innerHTML = "<div style=\'display:flex;gap:8px;align-items:center;color:#ff0000;\'><svg width=\'16\' height=\'16\' viewBox=\'0 0 24 24\' style=\'stroke:currentColor;fill:none;stroke-width:2;\'><circle cx=\'12\' cy=\'12\' r=\'10\'/><line x1=\'15\' y1=\'9\' x2=\'9\' y2=\'15\'/><line x1=\'9\' y1=\'9\' x2=\'15\' y2=\'15\'/></svg>Erreur</div>";
                }

                setTimeout(() => {
                    btn.innerHTML = "<div style=\'display:flex;gap:8px;align-items:center;\'><svg width=\'16\' height=\'16\' viewBox=\'0 0 24 24\' style=\'stroke:currentColor;fill:none;stroke-width:2;\'><path d=\'M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6\'/><circle cx=\'9\' cy=\'21\' r=\'1\'/><circle cx=\'20\' cy=\'21\' r=\'1\'/></svg>Ajouter au panier</div>";
                }, 2000);
            }

            function toggleDropdown(header) {
                const content = header.nextElementSibling;
                const arrow = header.querySelector(".dropdown-arrow");
                if (content.classList.contains("show")) {
                    content.classList.remove("show");
                    arrow.textContent = "▼";
                } else {
                    content.classList.add("show");
                    arrow.textContent = "▲";
                }
            }

            // Initialiser le premier accordéon comme ouvert au chargement de la page
            document.addEventListener("DOMContentLoaded", function() {
                const firstAccordion = document.querySelector(".accordion-content");
                const firstHeader = document.querySelector(".accordion-header");
                if(firstAccordion && firstHeader) {
                    firstAccordion.style.display = "block";
                    firstAccordion.classList.add("active");
                    firstHeader.querySelector(".arrow").innerHTML = "▲";
                }
            });
            </script>';

            return $output;

        } catch (ProductNotFoundException $e) {
            return '<div style="color:red">Erreur: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
    }
}

echo afficherCaracteristiquesProduitV2();