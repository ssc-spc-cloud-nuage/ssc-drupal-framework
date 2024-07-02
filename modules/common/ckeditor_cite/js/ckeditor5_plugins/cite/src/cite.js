import { Plugin } from 'ckeditor5/src/core';
import CiteEditing from './citeediting';
import CiteUI from './citeui';

export default class Cite extends Plugin {
	static get requires() {
		return [ CiteEditing, CiteUI ];
	}
}
