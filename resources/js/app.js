import { Editor, Extension, Mark, Node, mergeAttributes } from '@tiptap/core';
import StarterKit from '@tiptap/starter-kit';

const FontSize = Mark.create({
    name: 'fontSize',

    addAttributes() {
        return {
            size: {
                default: 12,
                parseHTML: element => Number.parseFloat(element.style.fontSize) || 12,
                renderHTML: attributes => ({ style: `font-size: ${attributes.size}pt` }),
            },
        };
    },

    parseHTML() {
        return [{ tag: 'span[style*="font-size"]' }];
    },

    renderHTML({ HTMLAttributes }) {
        return ['span', mergeAttributes(HTMLAttributes), 0];
    },
});

const LegalParagraphAttributes = Extension.create({
    name: 'legalParagraphAttributes',

    addGlobalAttributes() {
        return [{
            types: ['paragraph'],
            attributes: {
                textAlign: {
                    default: 'left',
                    parseHTML: element => element.style.textAlign || 'left',
                    renderHTML: attributes => ({ style: `text-align: ${attributes.textAlign}` }),
                },
                indent: {
                    default: 0,
                    parseHTML: element => Number.parseInt(element.dataset.indent || '0', 10),
                    renderHTML: attributes => ({
                        'data-indent': attributes.indent,
                        style: `margin-left: ${attributes.indent * 1.5}rem`,
                    }),
                },
            },
        }];
    },
});

const UdfTable = Node.create({
    name: 'table',
    group: 'block',
    content: 'tableRow+',
    isolating: true,
    parseHTML: () => [{ tag: 'table' }],
    renderHTML: ({ HTMLAttributes }) => ['table', mergeAttributes(HTMLAttributes), ['tbody', 0]],
});

const UdfTableRow = Node.create({
    name: 'tableRow',
    content: 'tableCell+',
    parseHTML: () => [{ tag: 'tr' }],
    renderHTML: ({ HTMLAttributes }) => ['tr', mergeAttributes(HTMLAttributes), 0],
});

const UdfTableCell = Node.create({
    name: 'tableCell',
    content: 'block+',
    isolating: true,

    addAttributes() {
        return {
            colspan: {
                default: 1,
                parseHTML: element => Number.parseInt(element.getAttribute('colspan') || '1', 10),
            },
        };
    },

    parseHTML: () => [{ tag: 'td' }],
    renderHTML: ({ HTMLAttributes }) => ['td', mergeAttributes(HTMLAttributes), 0],
});

const SafePaste = Extension.create({
    name: 'safePaste',
    priority: 110,

    transformPastedHTML(html) {
        const document = new DOMParser().parseFromString(html, 'text/html');
        document.querySelectorAll('script, style, iframe, object, embed, form').forEach(element => element.remove());
        document.querySelectorAll('*').forEach(element => {
            [...element.attributes].forEach(attribute => {
                if (attribute.name.startsWith('on') || ['href', 'src'].includes(attribute.name)) {
                    element.removeAttribute(attribute.name);
                }
            });
        });

        return document.body.innerHTML;
    },
});

const editorExtensions = () => [
    StarterKit.configure({
        blockquote: false,
        code: false,
        codeBlock: false,
        heading: false,
        link: false,
        strike: false,
    }),
    FontSize,
    LegalParagraphAttributes,
    UdfTable,
    UdfTableRow,
    UdfTableCell,
    SafePaste,
];

function insertTable(editor) {
    return editor.chain().focus().insertContent({
        type: 'table',
        content: Array.from({ length: 2 }, () => ({
            type: 'tableRow',
            content: Array.from({ length: 2 }, () => ({
                type: 'tableCell',
                attrs: { colspan: 1 },
                content: [{ type: 'paragraph', attrs: { textAlign: 'left', indent: 0 } }],
            })),
        })),
    }).run();
}

function changeIndent(editor, direction) {
    if (editor.isActive('listItem')) {
        return direction > 0
            ? editor.chain().focus().sinkListItem('listItem').run()
            : editor.chain().focus().liftListItem('listItem').run();
    }

    const current = Number(editor.getAttributes('paragraph').indent || 0);
    return editor.chain().focus().updateAttributes('paragraph', {
        indent: Math.max(0, Math.min(8, current + direction)),
    }).run();
}

function runToolbarCommand(editor, command) {
    const commands = {
        undo: () => editor.chain().focus().undo().run(),
        redo: () => editor.chain().focus().redo().run(),
        bold: () => editor.chain().focus().toggleBold().run(),
        italic: () => editor.chain().focus().toggleItalic().run(),
        underline: () => editor.chain().focus().toggleUnderline().run(),
        'align-left': () => editor.chain().focus().updateAttributes('paragraph', { textAlign: 'left' }).run(),
        'align-center': () => editor.chain().focus().updateAttributes('paragraph', { textAlign: 'center' }).run(),
        'align-right': () => editor.chain().focus().updateAttributes('paragraph', { textAlign: 'right' }).run(),
        'align-justify': () => editor.chain().focus().updateAttributes('paragraph', { textAlign: 'justify' }).run(),
        'bullet-list': () => editor.chain().focus().toggleBulletList().run(),
        'ordered-list': () => editor.chain().focus().toggleOrderedList().run(),
        indent: () => changeIndent(editor, 1),
        outdent: () => changeIndent(editor, -1),
        table: () => insertTable(editor),
        'horizontal-rule': () => editor.chain().focus().setHorizontalRule().run(),
    };

    commands[command]?.();
}

function updateToolbar(toolbar, editor) {
    const activeCommands = {
        bold: editor.isActive('bold'),
        italic: editor.isActive('italic'),
        underline: editor.isActive('underline'),
        'align-left': editor.getAttributes('paragraph').textAlign === 'left',
        'align-center': editor.getAttributes('paragraph').textAlign === 'center',
        'align-right': editor.getAttributes('paragraph').textAlign === 'right',
        'align-justify': editor.getAttributes('paragraph').textAlign === 'justify',
        'bullet-list': editor.isActive('bulletList'),
        'ordered-list': editor.isActive('orderedList'),
    };

    toolbar.querySelectorAll('[data-editor-command]').forEach(button => {
        button.dataset.active = activeCommands[button.dataset.editorCommand] ? 'true' : 'false';
    });
}

function initializeUdfEditor(element) {
    const source = document.getElementById(element.dataset.contentSource);
    if (!source) {
        return;
    }

    let content;
    try {
        content = JSON.parse(source.textContent);
    } catch {
        element.textContent = 'Belge içeriği yüklenemedi.';
        return;
    }

    const editable = element.dataset.mode === 'edit';
    const toolbar = editable ? document.querySelector('[data-udf-toolbar]') : null;
    const editor = new Editor({
        element,
        extensions: editorExtensions(),
        content,
        editable,
        editorProps: {
            attributes: {
                class: 'udf-editor-content',
                spellcheck: editable ? 'true' : 'false',
            },
        },
        onSelectionUpdate: ({ editor: activeEditor }) => toolbar && updateToolbar(toolbar, activeEditor),
        onTransaction: ({ editor: activeEditor }) => toolbar && updateToolbar(toolbar, activeEditor),
    });

    if (!editable || !toolbar) {
        return;
    }

    toolbar.querySelectorAll('[data-editor-command]').forEach(button => {
        button.addEventListener('click', () => runToolbarCommand(editor, button.dataset.editorCommand));
    });

    toolbar.querySelector('[data-editor-font-size]')?.addEventListener('change', event => {
        editor.chain().focus().setMark('fontSize', { size: Number(event.target.value) }).run();
    });

    const form = document.getElementById('udf-editor-form');
    const status = form?.querySelector('[data-udf-status]');
    const submit = form?.querySelector('[data-udf-submit]');

    form?.addEventListener('submit', async event => {
        event.preventDefault();
        submit.disabled = true;
        status.className = 'mt-4 min-h-6 text-sm text-slate-600';
        status.textContent = 'Yeni UDF sürümü hazırlanıyor…';

        try {
            const response = await fetch(form.action, {
                method: 'PUT',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': form.querySelector('[name="_token"]').value,
                },
                body: JSON.stringify({
                    document_version: Number(form.dataset.version),
                    content: editor.getJSON(),
                }),
            });
            const payload = await response.json();

            if (!response.ok) {
                const validationMessage = payload.errors
                    ? Object.values(payload.errors).flat()[0]
                    : payload.message;
                throw new Error(validationMessage || 'UDF sürümü oluşturulamadı.');
            }

            status.className = 'mt-4 min-h-6 text-sm font-medium text-emerald-700';
            status.textContent = payload.message;
            window.location.assign(payload.redirect);
        } catch (error) {
            status.className = 'mt-4 min-h-6 text-sm font-medium text-red-700';
            status.textContent = error.message || 'UDF sürümü oluşturulamadı.';
            submit.disabled = false;
        }
    });

    updateToolbar(toolbar, editor);
}

document.querySelectorAll('[data-udf-editor]').forEach(initializeUdfEditor);

/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allow your team to quickly build robust real-time web applications.
 */

import './echo';
import './notifications';
import './messages';
