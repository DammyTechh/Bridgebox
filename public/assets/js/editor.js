const initWysiwyg = () => {
    document.querySelectorAll('textarea[data-wysiwyg]').forEach((textarea) => {
        const wrapper = document.createElement('div');
        wrapper.className = 'wysiwyg-wrapper';

        const editorDiv = document.createElement('div');
        editorDiv.className = 'wysiwyg-editor';
        wrapper.appendChild(editorDiv);

        textarea.parentNode.insertBefore(wrapper, textarea);
        textarea.style.display = 'none';

        const quill = new Quill(editorDiv, {
            theme: 'snow',
            modules: {
                toolbar: [
                    [{ header: [1, 2, 3, false] }],
                    ['bold', 'italic', 'underline', 'strike'],
                    [{ list: 'ordered' }, { list: 'bullet' }],
                    ['blockquote', 'code-block'],
                    ['link'],
                    ['clean'],
                ],
            },
        });

        if (textarea.value.trim()) {
            quill.clipboard.dangerouslyPasteHTML(textarea.value);
        }

        const form = textarea.closest('form');
        if (form) {
            form.addEventListener('submit', () => {
                const html = quill.getSemanticHTML();
                textarea.value = html === '<p></p>' ? '' : html;
            });
        }
    });
};

document.addEventListener('DOMContentLoaded', initWysiwyg);
