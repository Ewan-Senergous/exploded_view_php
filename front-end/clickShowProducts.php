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
                    const pos = parseInt(header.querySelector('span').textContent.match(/Position (\d+)/)[1]);
                    if (pos === parseInt(position)) {
                        return {
                            header: header,
                            content: header.nextElementSibling
                        };
                    }
                }
                return null;
            }

            function openAccordionForPosition(position) {
                const accordion = findAccordionByPosition(position);
                if (!accordion) return;
                
                resetAllPoints();
                
                // Mise à jour des points
                document.querySelectorAll('.piece-hover').forEach(point => {
                    const isCurrentPosition = point.getAttribute('data-position') === position.toString();
                    point.setAttribute('data-state', isCurrentPosition ? 'selected' : 
                        (point.getAttribute('data-exists') === 'true' ? 'normal' : 'invalid'));
                });

                // Fermer tous les accordéons et ouvrir celui sélectionné
                document.querySelectorAll('.accordion-content').forEach(content => {
                    const isTarget = content === accordion.content;
                    content.style.display = isTarget ? 'block' : 'none';
                    content.classList.toggle('active', isTarget);
                    content.previousElementSibling.querySelector('.arrow').innerHTML = isTarget ? '▲' : '▼';
                });

                // Scroll vers l'accordéon
                const scrollContainer = document.querySelector('.scroll-container');
                if (scrollContainer) {
                    const containerTop = scrollContainer.getBoundingClientRect().top;
                    const accordionTop = accordion.header.getBoundingClientRect().top;
                    scrollContainer.scrollTop += (accordionTop - containerTop);
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

            // Gestionnaire d'événements unifié pour click et touch
            const handleInteraction = (e) => {
                e.preventDefault();
                const position = e.currentTarget.getAttribute('data-position') || 
                    parseInt(e.currentTarget.querySelector('span').textContent.match(/Position (\d+)/)[1]);
                if (position) openAccordionForPosition(parseInt(position));
            };

            // Application des écouteurs d'événements
            document.querySelectorAll('.piece-hover, .accordion-header').forEach(element => {
                ['click', 'touchstart'].forEach(eventType => {
                    element.addEventListener(eventType, handleInteraction, { passive: false });
                });
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }
}

add_shortcode('clickShowProducts', 'clickShowProductsFunction');
?>