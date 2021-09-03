var pdfjsLib = window['pdfjs-dist/build/pdf'];

pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.6.347/pdf.worker.min.js';

var pdfDoc = null,
    pageNum = 1,
    pageRendering = false,
    pageNumPending = null,
    scale = 1,
    allPDFjsCanvas = document.querySelectorAll('canvas.pdfjs');

function renderPage(canvas, pdfDoc, num) {
    pageRendering = true;

    pdfDoc.getPage(num).then(function (page) {
        var viewport = page.getViewport({ scale: scale });
        canvas.height = viewport.height;
        canvas.width = viewport.width;

        var renderContext = {
            canvasContext: canvas.getContext('2d'),
            viewport: viewport
        };

        page.render(renderContext);
    });
}

/**
 * PDF async "download".
 */
allPDFjsCanvas.forEach(canvas => {
    pdfjsLib.getDocument(canvas.dataset.url).promise.then(function (pdfDoc) {
        renderPage(canvas, pdfDoc, pageNum);
    });
});

