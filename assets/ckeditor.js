import * as ClassicEditorBase from '@ckeditor/ckeditor5-build-classic';
import * as Alignment from '@ckeditor/ckeditor5-alignment/src/alignment';
import * as Font from '@ckeditor/ckeditor5-font/src/font';
import * as SourceEditing from '@ckeditor/ckeditor5-source-editing/src/sourceediting';
import * as Fullscreen from '@ckeditor/ckeditor5-fullscreen/src/fullscreen';

export default class ClassicEditor extends ClassicEditorBase {}

ClassicEditor.builtinPlugins = [
    ...ClassicEditorBase.builtinPlugins,
    Alignment,
    Font,
    SourceEditing,
    Fullscreen
];

ClassicEditor.defaultConfig = {
    toolbar: {
        items: [
            'undo', 'redo', '|',
            'bold', 'italic', 'underline', '|',
            'bulletedList', 'numberedList', '|',
            'link', 'imageUpload', '|',
            'alignment', '|',
            'fontColor', 'fontBackgroundColor', '|',
            'sourceEditing', 'fullscreen'
        ]
    },
    alignment: {
        options: [ 'left', 'center', 'right', 'justify' ]
    },
    fontColor: {
        colors: [
            { color: 'red', label: 'Rouge' },
            { color: 'green', label: 'Vert' },
            { color: 'blue', label: 'Bleu' },
            { color: 'black', label: 'Noir' }
        ]
    },
    fontBackgroundColor: {
        colors: [
            { color: 'yellow', label: 'Jaune' },
            { color: 'lightblue', label: 'Bleu clair' },
            { color: 'lightgreen', label: 'Vert clair' }
        ]
    },
    language: 'fr'
};
