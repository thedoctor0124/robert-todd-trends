<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no">
<meta name="google" content="notranslate">
<meta name="robots" content="noindex, nofollow">
<title>{{ $publication->title }}</title>

<script type="text/javascript" src="/flipbook-viewer/flipbook/js/site/jquery-3.5.1.min.js?v2"></script>
<script src="/flipbook-viewer/flipbook/js/site/pdf.min.js" type="module"></script>

<script type="text/javascript">
    PDFJS_WORKER = '/flipbook-viewer/flipbook/js/site/pdf.worker.min.js?v5';
    PDFJS_CMAP_URL = '/flipbook-viewer/flipbook/js/site/bcmaps/';
    PDFJS_WASM_URL = '/flipbook-viewer/flipbook/js/site/wasm/';

    var flipbookcfg = {
        "id": "pub_{{ $publication->id }}",
        "app_name": @json(config('app.name')),
        "app_ver": "1",
        "name": @json($publication->title),
        "mode": "PDF",
        "isize": 1,
        "width": {{ (int) ($pdfMeta['width'] ?? 1684) }},
        "height": {{ (int) ($pdfMeta['height'] ?? 2384) }},
        "size": @json((string) ($pdfMeta['size'] ?? 0)),
        "num_pages": @json($pdfMeta['num_pages'] ?? null),
        "cover": 0,
        "viewer": "MAGAZINE",
        "stats": 0,
        "readerToken": null,
        "design": {
            "type": "magazine",
            "background": "/flipbook-viewer/files/backgrounds/back5.svg",
            "background_color": null,
            "background_style": {"blur":"0","transparency":"40","size":"Cover","position":"center center"},
            "company_logo": "",
            "company_logo_link": "",
            "company_logo_link_mode": 0,
            "company_logo_style": "{}",
            "title": "",
            "subtitle": "",
            "description": "",
            "show_slider": 3,
            "show_download": 0,
            "show_print": 0,
            "show_fullscreen": 1,
            "show_text": 0,
            "show_shadow": 1,
            "show_depth": 1,
            "show_edges": 0,
            "show_round": 0,
            "show_binding": 0,
            "show_center": 1,
            "show_double": 0,
            "show_zoom": 1,
            "show_share": 0,
            "show_search": 0,
            "show_thumbpanel": 0,
            "show_outline": 0,
            "show_bookmarks": 0,
            "start_page": null,
            "end_page": null,
            "load_page": null,
            "click_zoom": 2,
            "show_prevnext": 0,
            "show_start": 0,
            "show_end": 0,
            "viewer_dir": 0,
            "rtl": 0,
            "arrows": 1,
            "controls_iconset": "iconset2_6",
            "controls_size": "md",
            "controls_style": "background-color:rgba(255, 255, 255, 0.85);top: 20px; bottom: auto; left: auto; right: 50px; flex-direction: row; padding-left: 10px; padding-right: 10px; padding-top: 6px; padding-bottom: 6px;",
            "sound_flip": 1
        },
        "layers": null,
        "lead": null,
        "bookmark": {"list":[]}
    };

    CDN_PATH = '/flipbook-viewer/';
    THUMBNAIL_PATH = '/flipbook-viewer/';
    TOC_PATH = '/flipbook-viewer/files/toc/';
    ICONSET_VER = '6';
</script>
</head>
<body style="width: 100vw; height: 100vh; margin: 0; padding: 0; overflow: hidden;">

<div id="loaderLine" class="loader-line" style="display: none;"></div>
<div class="logo-backs"></div>
<div id="modalFull" style="display: none;">
    <div class="hz-icon hz-icn-fullscreen-on" style="background-image: url('/flipbook-viewer/flipbook/img/iconset2_6.png')"></div>
</div>
<div id="modalOver" style="display: none;"></div>
<div id="zoomArea"></div>

<div id="canvas" style="width: 100%; height: 100%; margin: 0 auto; position: relative; box-sizing: border-box; padding: 10px;">
    @include('flipbook-controls')
</div>

<link href="/flipbook-viewer/flipbook/css/magazine.css" rel="stylesheet">
<link href="/flipbook-viewer/flipbook/css/prod5.min.css?v2=6&v=710" rel="stylesheet">
<script type="text/javascript" src="/flipbook-viewer/flipbook/js/prod5.min.js?v=710"></script>
<script type="text/javascript" src="/flipbook-viewer/flipbook/js/prodhzp.min.js?v=710"></script>

<script type="text/javascript">
    async function syncFlipbookPdfMetadata(pdfSrc) {
        try {
            let pdfjs = window.pdfjsLib;

            if (!pdfjs) {
                const module = await import('/flipbook-viewer/flipbook/js/site/pdf.min.js');
                pdfjs = module.getDocument ? module : window.pdfjsLib;
            }

            if (!pdfjs || !pdfjs.getDocument) {
                throw new Error('PDF.js is unavailable.');
            }

            if (pdfjs.GlobalWorkerOptions) {
                pdfjs.GlobalWorkerOptions.workerSrc = '/flipbook-viewer/flipbook/js/site/pdf.worker.min.js?v5';
            }

            const pdf = await pdfjs.getDocument({
                url: pdfSrc,
                cMapUrl: '/flipbook-viewer/flipbook/js/site/bcmaps/',
                cMapPacked: true,
                wasmUrl: '/flipbook-viewer/flipbook/js/site/wasm/'
            }).promise;

            flipbookcfg.num_pages = pdf.numPages;

            const firstPage = await pdf.getPage(1);
            const viewport = firstPage.getViewport({ scale: 1 });
            flipbookcfg.width = Math.max(1, Math.round(viewport.width * 2));
            flipbookcfg.height = Math.max(1, Math.round(viewport.height * 2));

            try {
                const response = await fetch(pdfSrc, { method: 'HEAD' });
                const contentLength = response.headers.get('content-length');

                if (contentLength) {
                    flipbookcfg.size = contentLength;
                }
            } catch (error) {
                console.warn('Unable to read PDF file size for flipbook.', error);
            }
        } catch (error) {
            console.warn('Unable to read PDF metadata for flipbook.', error);
            delete flipbookcfg.num_pages;
        }
    }

    $(() => {
        if (typeof heyzineDesign !== 'undefined') {
            heyzineDesign.load(flipbookcfg.design);
        }
        
        var pdfSrc = @json($pdfUrl);
        var metadataTimeout = new Promise((resolve) => {
            setTimeout(() => {
                console.warn('Timed out reading PDF metadata for flipbook; loading with available config.');
                resolve();
            }, 5000);
        });

        Promise.race([syncFlipbookPdfMetadata(pdfSrc), metadataTimeout]).then(() => heyzine.load(pdfSrc, flipbookcfg)).then(() => {
            if (typeof heyzineDesign !== 'undefined') {
                heyzineDesign.afterLoad(flipbookcfg.design);
            }
        });
    });
</script>

</body>
</html>
