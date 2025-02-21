<?php
if (!function_exists('clickShowProductsFunction')) {
    function clickShowProductsFunction() {
        ob_start();
        ?>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Constantes de couleurs pour éviter la répétition
            const COLORS = {
                default: {
                    bg: 'rgba(255, 0, 0, 0.3)',
                    border: 'rgba(255, 0, 0, 0.5)'
                },
                selected: {
                    bg: 'rgba(0, 86, 179, 0.3)',
                    border: 'rgba(0, 86, 179, 0.5)'
                }
            };

            function resetAllPoints() {
                document.querySelectorAll('.piece-hover').forEach(point => {
                    
                    const exists = point.getAttribute('data-exists') === 'true';
                    point.style.backgroundColor = COLORS.default.bg;
                    point.style.borderColor = COLORS.default.border;
                    point.setAttribute('data-selected', 'false');
                });
            }

           
            function findAccordionByPosition(position) {
                const headers = document.querySelectorAll('.accordion-header');
                for (let header of headers) {
                    const posText = header.querySelector('span').textContent;
                    // Ignorer si c'est un kit
                    if (posText.includes('Maintenance')) {
                        continue;
                    }
                    const matches = posText.match(/Position (\d+)/);
                    if (matches && parseInt(matches[1]) === parseInt(position)) {
                        return {
                            header: header,
                            content: header.nextElementSibling
                        };
                    }
                }
                return null;
            }

            function openAccordionForPosition(position, fromAccordion = false) {
                const accordion = findAccordionByPosition(position);
                if (!accordion) return;

                resetAllPoints();

                // Mise à jour des points
                document.querySelectorAll('.piece-hover').forEach(point => {
                    const isCurrentPosition = point.getAttribute('data-position') === position.toString();
                    point.setAttribute('data-state', isCurrentPosition ? 'selected' :
                        (point.getAttribute('data-exists') === 'true' ? 'normal' : 'invalid'));
                });

                // Ne pas modifier l'accordéon si le clic vient de l'accordéon lui-même
                if (!fromAccordion) {
                    // Fermer tous les accordéons d'abord
                    document.querySelectorAll('.accordion-content').forEach(content => {
                        content.style.display = 'none';
                        content.classList.remove('active');
                        content.previousElementSibling.querySelector('.arrow').innerHTML = '▼';
                    });

                    // Ouvrir l'accordéon sélectionné
                    accordion.content.style.display = 'block';
                    accordion.content.classList.add('active');
                    accordion.header.querySelector('.arrow').innerHTML = '▲';

                    // Scroll vers l'accordéon
                    const scrollContainer = document.querySelector('.scroll-container');
                    if (scrollContainer) {
                        const containerTop = scrollContainer.getBoundingClientRect().top;
                        const accordionTop = accordion.header.getBoundingClientRect().top;
                        scrollContainer.scrollTop += (accordionTop - containerTop);
                    }
                }

                // Mise à jour du point sélectionné
                document.querySelectorAll(`.piece-hover[data-position="${position}"]`).forEach(point => {
                    if (point.getAttribute('data-exists') === 'true') {
                        point.style.backgroundColor = COLORS.selected.bg;
                        point.style.borderColor = COLORS.selected.border;
                        point.setAttribute('data-selected', 'true');
                    }
                });
            }
                
            // Gestionnaire d'événements séparés pour les points et les accordéons
            document.querySelectorAll('.piece-hover').forEach(point => {
                ['click', 'touchstart'].forEach(eventType => {
                    point.addEventListener(eventType, (e) => {
                        e.preventDefault();
                        const position = point.getAttribute('data-position');
                        if (position) openAccordionForPosition(parseInt(position), false);
                    }, { passive: false });
                });
            });

            document.querySelectorAll('.accordion-header').forEach(header => {
                ['click', 'touchstart'].forEach(eventType => {
                    header.addEventListener(eventType, (e) => {
                        const posText = header.querySelector('span').textContent;
                        if (posText.includes('Maintenance')) return;
                        
                        const matches = posText.match(/Position (\d+)/);
                        if (matches) {
                            const position = parseInt(matches[1]);
                            openAccordionForPosition(position, true);
                        }
                    }, { passive: false });
                });
            });
        });
        </script>
         <?php
        return ob_get_clean();
    }
}

add_shortcode('clickShowProducts', 'clickShowProductsFunction');
