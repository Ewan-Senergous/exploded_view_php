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

    // Modifier la fonction isValidPosition pour accepter les positions avec *
    function isValidPosition($position) {
        $position = ltrim($position, '*');
        return is_numeric($position) && intval($position) >= 1 && intval($position) <= 10000;
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
                .position-text {
                    font-weight: 600;
                    font-size: 18px;
                }
                .product-name {
                    font-weight: 600;
                    font-size: 18px;
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
                    .scroll-container {
                        max-height: 400px !important;
                    }
                    .red-alert {
                        flex-direction: column-reverse;
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
                    .product-info-row.quantity-row {
                        flex-direction: row;
                    }
                    .product-info-row span {
                        min-width: 100%;
                    }
                    .product-info-row.quantity-row span {
                        min-width: unset;
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
            </style>';

            // Dans votre tableau.php
            if (isset($jsonData['table_data'])) {
                $output .= '<div id="scroll-container" class="scroll-container">';
                
                // Filtrer les données pour n'avoir que les positions valides
                $filtered_data = array_filter($jsonData['table_data'], function($piece) {
                    return isset($piece['position_vue_eclatee']) && isValidPosition($piece['position_vue_eclatee']);
                });

                // Séparer les pièces spéciales (*) des pièces normales
                $special_parts = array_filter($filtered_data, function($piece) {
                    return strpos($piece['position_vue_eclatee'], '*') === 0;
                });
                
                $normal_parts = array_filter($filtered_data, function($piece) {
                    return strpos($piece['position_vue_eclatee'], '*') !== 0;
                });

                // Trier chaque groupe séparément
                usort($special_parts, function($a, $b) {
                    return intval(ltrim($a['position_vue_eclatee'], '*')) - intval(ltrim($b['position_vue_eclatee'], '*'));
                });

                usort($normal_parts, function($a, $b) {
                    return intval($a['position_vue_eclatee']) - intval($b['position_vue_eclatee']);
                });

                // Fusionner les tableaux avec les pièces spéciales en premier
                $filtered_data = array_merge($special_parts, $normal_parts);

                foreach ($filtered_data as $index => $piece) {
                    $sku = htmlspecialchars($piece['reference_piece']);
                    $nom_piece = htmlspecialchars($piece['nom_piece']);
                    $variation_id = getProductVariationIdBySku($sku);
                    $position = $piece['position_vue_eclatee'];
                    $isSpecialPart = strpos($position, '*') === 0;
                    $displayPosition = ltrim($position, '*');

                    // Déterminer le numéro de kit pour les pièces spéciales
                    $kitNumber = '';
                    if ($isSpecialPart) {
                        $kitNumber = 'Kit ' . (array_search($piece, $special_parts) + 1) . ' - ';
                    }
                    
                    $output .= sprintf('
                    <div class="accordion">
                        <div class="accordion-header %s" onclick="toggleAccordion(%d, event)">
                             <span><strong>Position %s - %s</strong><span class="product-name">%s</span></span>
                            <span class="arrow">▼</span>
                        </div>
                        <div id="accordion-%d" class="accordion-content %s">
                            <div class="product-content-container">',
                        $isSpecialPart ? 'special' : 'normal',
                        $index,
                        $displayPosition,
                        $kitNumber,
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
                                '<div class="product-info-row quantity-row" style="display:flex;">
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
                    $orderedFields = $isSpecialPart ?
                        ['nom_model', 'reference_model', 'reference_piece'] :
                        ['nom_model', 'reference_model', 'reference_piece', 'contenu_dans_kit'];

                    foreach ($orderedFields as $field) {
                        if (isset($piece[$field])) {
                            $label = [
                                'nom_model' => 'Nom du modèle',
                                'reference_model' => 'Référence modèle',
                                'reference_piece' => 'Référence pièce',
                                'nom_piece' => 'Nom de la pièce',
                                'contenu_dans_kit' => 'Contenu dans le kit'
                            ][$field];
                            
                            if ($field === 'contenu_dans_kit') {
                                $value = isEmptyOrSpecialChar($piece[$field])
                                    ? '<span style="color: red; font-weight: bold;">N\'EST COMPRIS DANS AUCUN KIT</span>'
                                    : HTML_STRONG_OPEN . htmlspecialchars($piece[$field]) . HTML_STRONG_CLOSE;
                            } else {
                                $value = isEmptyOrSpecialChar($piece[$field])
                                    ? '<span style="color: red; font-weight: bold;">VALEUR N\'EXISTE PAS</span>'
                                    : HTML_STRONG_OPEN . htmlspecialchars($piece[$field]) . HTML_STRONG_CLOSE;
                            }
                            
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
                                <div id="alert-%d" style="display:none;margin-top:10px;padding:15px;border-radius:5px;background-color:#4CAF50;color:white;font-weight:bold;text-align:center">
                                    <div style="display:flex;justify-content:space-between;align-items:center;gap:5px">
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
            async function ajouterAuPanier(reference, productId) {
                const btn = event.currentTarget;
                const qty = btn.parentElement.querySelector("input[type=number]").value;

                // Vérifier si le productId est égal à 0
                if (productId === 0) {
                    
                    const errorAlert = document.createElement(\'div\');
                    errorAlert.style.cssText = \'margin-top:10px;padding:15px;border-radius:5px;background-color:#FF0000;color:white;font-weight:bold;text-align:center\';
                    errorAlert.innerHTML = `
                        <div class="red-alert" style="display:flex;justify-content:space-between;align-items:center;gap:5px">
                            <span>X Produit non trouvé dans la base de données</span>
                            <a href="https://www.cenov-distribution.fr/nous-contacter/"
                               style="background-color:white;color:#FF0000;padding:8px 15px;border-radius:4px;text-decoration:none;font-weight:bold;transition:all .3s">
                               Nous contacter
                            </a>
                        </div>
                    `;
                    
                    btn.parentElement.insertAdjacentElement(\'afterend\', errorAlert);
                    
                    return;
                }

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

                    // Si on a un ID produit valide, on considère que le produit est ajouté
                    if (productId !== 0) {
                        console.log("✅ SUCCÈS: Produit ajouté au panier!", {
                            sku: reference,
                            productId: productId
                        });
                        
                        btn.innerHTML = "<div style=\'display:flex;gap:8px;align-items:center;\'><svg width=\'16\' height=\'16\' viewBox=\'0 0 24 24\' style=\'stroke:currentColor;fill:none;stroke-width:2;\'><path d=\'M20 6L9 17l-5-5\'/></svg>Ajouté !</div>";
                        
                        const alertElement = document.getElementById(`alert-${productId}`);
                        if (alertElement) alertElement.style.display = "block";

                        jQuery(document.body).trigger("wc_fragments_refreshed");
                    }

                } catch (error) {
                    console.log("❌ ERREUR:", error.message);
                }

                setTimeout(() => {
                    btn.innerHTML = "<div style=\'display:flex;gap:8px;align-items:center;\'><svg width=\'16\' height=\'16\' viewBox=\'0 0 24 24\' style=\'stroke:currentColor;fill:none;stroke-width:2;\'><path d=\'M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6\'/><circle cx=\'9\' cy=\'21\' r=\'1\'/><circle cx=\'20\' cy=\'21\' r=\'1\'/></svg>Ajouter au panier</div>";
                }, 2000);
            }
            </script>';

            return $output;

        } catch (ProductNotFoundException $e) {
            return '<div style="color:red">Erreur: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
    }
}

echo afficherCaracteristiquesProduitV2();