<?php
if (!function_exists('afficher_caracteristiques_produit_v2')) {
    function get_product_variation_id_by_sku($sku) {
        return ($id = wc_get_product_id_by_sku($sku)) ? $id : 0;
    }

    function afficher_caracteristiques_produit_v2() {
        try {
            $product = wc_get_product(get_the_ID()) ?? $GLOBALS['product'] ?? throw new Exception('Produit non trouvé');

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
                @media (max-width: 1900px) {
                    .product-info-row {
                        flex-direction: column;
                        margin: 15px 0 ;
                    }
                    .product-info-row span {
                        min-width: 100% ;
                    }
                    .actions-container {
                        flex-direction: column ;
                        align-items: stretch ;
                        gap: 15px ;
                    }
                    .quantity-container {
                        justify-content: center ;
                    }
                }
            </style>';

            // Dans votre tableau.php
            if (isset($jsonData['table_data'])) {
                $output .= '<div id="scroll-container" class="scroll-container">';
                

                foreach ($jsonData['table_data'] as $index => $piece) {
                    $sku = htmlspecialchars($piece['Ref_fabriquant']);
                    $nom_produit = htmlspecialchars($piece['Nom_produit']);
                    $variation_id = get_product_variation_id_by_sku($sku);

                    $output .= sprintf('
                    <div class="accordion">
                       <div class="accordion-header" onclick="toggleAccordion(%d, event)">
                            <span>%s - <span class="product-name">%s</span></span>
                            <span class="arrow">▼</span>
                        </div>
                        <div id="accordion-%d" class="accordion-content %s">
                            <div style="background:white;border-radius:5px">
                                %s
                                <div class="actions-container" style="display:flex;justify-content:flex-start;align-items:start;gap:10px;margin-top:20px">
                                    <div class="quantity-container" style="display:flex;align-items:center;gap:10px">
                                        <label style="color:#2c5282">Quantité :</label>
                                        <div style="display:flex;align-items:center">
                                            <button onclick="this.nextElementSibling.stepDown()" style="background:#f7fafc;border:1px solid #e2e8f0;padding:5px 9px;cursor:pointer">-</button>
                                            <input type="number" value="1" min="1" style="width:50px;text-align:center;border-top:1px solid #e2e8f0;border-bottom:1px solid #e2e8f0;border-left:none;border-right:none;padding:7px 0">
                                            <button onclick="this.previousElementSibling.stepUp()" style="background:#f7fafc;border:1px solid #e2e8f0;padding:5px 9px;cursor:pointer">+</button>
                                        </div>
                                    </div>
                                    <button onclick="ajouterAuPanier(\'%s\',%d)" class="add-to-cart-btn" style="color:white;padding:6px 8px;border:none;border-radius:5px;cursor:pointer;font-weight:bold;display:flex;align-items:center;gap:8px">
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
                    $index,
                    '<strong>Position ' . ($index + 1) . '</strong>',
                    '<strong>' . $nom_produit . '</strong>',
                    $index,
                    $index === 0 ? 'active' : '',
                    implode('', array_map(fn($k, $v) => (!empty($k) && !empty($v) && $k !== "vide" && $v !== "vide") ?
                        sprintf('<div class="product-info-row" style="display:flex;margin:10px 0;"><span style="color:#2c5282;min-width:120px">%s&nbsp;:&nbsp;</span><span>%s</span></div>',
                        htmlspecialchars($k),
                        ($k === 'Ref_fabriquant' || $k === 'Nom_produit') ? '<strong>' . htmlspecialchars($v) . '</strong>' : htmlspecialchars($v)
                        ) : '', array_keys($piece), $piece)),
                    $sku,
                    $variation_id,
                    $variation_id,
                    wc_get_cart_url()
                    );
                }
                $output .= '</div>'; // fin de scroll-container

                // Ajouter le conteneur bouton de zoom en dessous
                $output .= '
                <div class="zoom-controls">
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

        } catch (Exception $e) {
            return '<div style="color:red">Erreur: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
    }
}

echo afficher_caracteristiques_produit_v2();