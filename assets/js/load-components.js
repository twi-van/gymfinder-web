async function loadComponents() {
    const rootPath = window.ROOT_PATH || '';

    // Load Header
    const headerPlaceholder = document.getElementById('header');
    if (headerPlaceholder) {
        try {
            const res = await fetch(rootPath + 'components/header.html');
            let html = await res.text();
            
            // Rewrite URLs
            html = html.replace(/href="([^"]+)"/g, (match, p1) => {
                if (p1.startsWith('http') || p1.startsWith('#') || p1.startsWith('mailto')) return match;
                return `href="${rootPath}${p1}"`;
            });
            html = html.replace(/src="([^"]+)"/g, (match, p1) => {
                if (p1.startsWith('http') || p1.startsWith('data:')) return match;
                return `src="${rootPath}${p1}"`;
            });

            headerPlaceholder.outerHTML = html;

            // Trigger event for auth.js to update the header
            if (window.jQuery) {
                $(document).trigger('headerLoaded');
            } else {
                document.dispatchEvent(new Event('headerLoaded'));
            }

            // Highlight active link
            const currentPath = window.location.pathname;
            document.querySelectorAll('.nav-link').forEach(link => {
                link.classList.remove('active', 'fw-semibold', 'text-primary');
                const linkPath = new URL(link.href).pathname;
                if (currentPath === linkPath || (currentPath.endsWith('/') && linkPath.endsWith('index.html'))) {
                    link.classList.add('active', 'fw-semibold', 'text-primary');
                }
            });
        } catch (e) {
            console.error('Error loading header:', e);
        }
    }

    // Load Footer
    const footerPlaceholder = document.getElementById('footer');
    if (footerPlaceholder) {
        try {
            const res = await fetch(rootPath + 'components/footer.html');
            let html = await res.text();
            
            // Rewrite URLs
            html = html.replace(/href="([^"]+)"/g, (match, p1) => {
                if (p1.startsWith('http') || p1.startsWith('#') || p1.startsWith('mailto')) return match;
                return `href="${rootPath}${p1}"`;
            });
            html = html.replace(/src="([^"]+)"/g, (match, p1) => {
                if (p1.startsWith('http') || p1.startsWith('data:')) return match;
                return `src="${rootPath}${p1}"`;
            });

            footerPlaceholder.outerHTML = html;
        } catch (e) {
            console.error('Error loading footer:', e);
        }
    }
}

document.addEventListener('DOMContentLoaded', loadComponents);
