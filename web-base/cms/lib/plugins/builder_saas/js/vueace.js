/* START: <ace-editor> Vue component */
(function () {
	var PROPS = {
		selectionStyle: {},
		highlightActiveLine: { f: toBool },
		highlightSelectedWord: { f: toBool },
		readOnly: { f: toBool },
		cursorStyle: {},
		mergeUndoDeltas: { f: toBool },
		behavioursEnabled: { f: toBool },
		wrapBehavioursEnabled: { f: toBool },
		enableBasicAutocompletion: { v: true },
		enableSnippets: { v: true },
		enableLiveAutocompletion: { v: true },
		autoScrollEditorIntoView: { f: toBool, v: false },
		copyWithEmptySelection: { f: toBool },
		useSoftTabs: { f: toBool, v: false },
		navigateWithinSoftTabs: { f: toBool, v: false },
		hScrollBarAlwaysVisible: { f: toBool },
		vScrollBarAlwaysVisible: { f: toBool },
		highlightGutterLine: { f: toBool },
		animatedScroll: { f: toBool },
		showInvisibles: { f: toBool },
		showPrintMargin: { f: toBool },
		printMarginColumn: { f: toNum, v: 80 },
		enableEmmet: { v: true },
		// shortcut for showPrintMargin and printMarginColumn
		printMargin: { f: function (x) { return toBool(x, true) && toNum(x); } }, // false|number
		fadeFoldWidgets: { f: toBool },
		showFoldWidgets: { f: toBool, v: true },
		showLineNumbers: { f: toBool, v: true },
		showGutter: { f: toBool, v: true },
		displayIndentGuides: { f: toBool, v: true },
		fontSize: {},
		fontFamily: {},
		minLines: { f: toNum },
		maxLines: { f: toNum },
		scrollPastEnd: { f: toBoolOrNum },
		fixedWidthGutter: { f: toBool, v: false },
		theme: { v: 'chrome' },
		scrollSpeed: { f: toNum },
		dragDelay: { f: toNum },
		dragEnabled: { f: toBool, v: true },
		focusTimeout: { f: toNum },
		tooltipFollowsMouse: { f: toBool },
		firstLineNumber: { f: toNum, v: 1 },
		overwrite: { f: toBool },
		newLineMode: {},
		useWorker: { f: toBool },
		tabSize: { f: toNum, v: 2 },
		wrap: { f: toBoolOrNum },
		foldStyle: { v: 'markbegin' },
		mode: { v: 'javascript' },
		value: {},
	};

	var EDITOR_EVENTS = ['blur', 'change', 'changeSelectionStyle', 'changeSession', 'copy', 'focus', 'paste'];

	var INPUT_EVENTS = ['keydown', 'keypress', 'keyup'];

	function toBool(value, opt_ignoreNum) {
		var result = value;
		if (result != null) {
			(value + '').replace(
				/^(?:|0|false|no|off|(1|true|yes|on))$/,
				function(m, isTrue) {
					result = (/01/.test(m) && opt_ignoreNum) ? result : !!isTrue;
				}
			);
		}
		return result;
	}

	function toNum(value) {
		return (value == null || isNaN(+value)) ? value : +value;
	}

	function toBoolOrNum(value) {
		var result = toBool(value, true);
		return 'boolean' === typeof result ? result : toNum(value);
	}

	function emit(component, name, event) {
		component.$emit(name.toLowerCase(), event);
		if (name !== name.toLowerCase()) {
			component.$emit(
				name.replace(/[A-Z]+/g, function(m) { return ('-' + m).toLowerCase(); }),
				event
			);
		}
	}

	// Defined for IE11 compatibility
	function entries(obj) {
		return Object.keys(obj).map(function(key) { return [key, obj[key]]; });
	}

	Vue.component('aceEditor', {
		template: '<div ref="root"></div>',
		props: Object.keys(PROPS),
		data: function() {
			return {
				editor: null,
				langTools:null,
				keybinding:null,
				isShowingError: false,
				isShowingWarning: false,
				allowInputEvent: true,
				// NOTE:  "lastValue" is needed to prevent cursor from always going to
				// the end after typing
				lastValue: ''
			};
		},
		methods: {
			setOption: function(key, value) {
				var func = PROPS[key].f;

				value = /^(theme|mode)$/.test(key)
					? 'ace/' + key + '/' + value
				: func
					? func(value)
				: value;

				this.editor.setOption(key, value);
			}
		},
		watch: (function () {
			var watch = {
				value: function(value) {
					//if (value && this.lastValue !== value) {
					if (this.lastValue !== value) {
						this.allowInputEvent = false;
						this.editor.setValue(value);
						this.allowInputEvent = true;
					}
				}
			};

			return entries(PROPS).reduce(
				function(watch, propPair) {
					var propName = propPair[0];
					if (propName !== 'value') {
						watch[propName] = function (newValue) {
							this.setOption(propName, newValue);
						};
					}
					return watch;
				},
				watch
			);
		})(),
		mounted: function() {
			var self = this;

			self.editor = window.ace.edit(self.$refs.root, { value: self.value });

			self.langTools = window.ace.require("ace/ext/language_tools");


			self.editor = window.ace.edit(self.$refs.root, { value: self.value });
			//self.editor.setKeyboardHandler("ace/keyboard/sublime");
			self.editor.commands.addCommand({
				name: "showKeyboardShortcuts",
				bindKey: {win: "Ctrl-Alt-h", mac: "Command-Alt-h"},
				exec: function(editor) {
					window.ace.config.loadModule("ace/ext/keybinding_menu", function(module) {
						module.init(editor);
						//self.editor.showKeyboardShortcuts()
					})
				}
			})
			self.editor.execCommand("showKeyboardShortcuts")


			// AUTOCOMPLETE
			var myList = [
				{name:`pmenu`,description: `<? CustomCode::Menu(0,["liClass" => "uppercase block px-4 py-2 lg:py-0 hover:text-blue-400 navbar-link"]);?>`},
				{name:`tmenu`,description: `
<nav class="menutail flex items-center justify-between flex-wrap bg-blue-800 p-6">
	<div class="flex items-center flex-shrink-0 text-white mr-6">
		<svg class="fill-current h-8 w-8 mr-2" width="54" height="54" viewBox="0 0 54 54" xmlns="http://www.w3.org/2000/svg"><path d="M13.5 22.1c1.8-7.2 6.3-10.8 13.5-10.8 10.8 0 12.15 8.1 17.55 9.45 3.6.9 6.75-.45 9.45-4.05-1.8 7.2-6.3 10.8-13.5 10.8-10.8 0-12.15-8.1-17.55-9.45-3.6-.9-6.75.45-9.45 4.05zM0 38.3c1.8-7.2 6.3-10.8 13.5-10.8 10.8 0 12.15 8.1 17.55 9.45 3.6.9 6.75-.45 9.45-4.05-1.8 7.2-6.3 10.8-13.5 10.8-10.8 0-12.15-8.1-17.55-9.45-3.6-.9-6.75.45-9.45 4.05z"/></svg>
		<span class="font-semibold text-xl tracking-tight">Tailwind CSS</span>
	</div>
	<div class="block lg:hidden" onclick="document.querySelector('.navbar').classList.toggle('hidden')">
		<button class="flex items-center px-3 py-2 border rounded text-teal-200 border-teal-400 hover:text-white hover:border-white">
			<svg class="fill-current h-3 w-3" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><title>Menu</title><path d="M0 3h20v2H0V3zm0 6h20v2H0V9zm0 6h20v2H0v-2z"/></svg>
		</button>
	</div>
	<menu class="text-lg lg:flex-grow lg:text-right pb-8 lg:pb-4 pt-4 px-4">
		<li class="cursor-pointer block mt-4 lg:inline-block lg:mt-0 text-teal-100 hover:text-white mx-4 relative">
			<a href="#responsive-header">Menu option</a>
			<ul class="block lg:absolute left-0 mt-2 bg-white p-2 text-sm text-teal-700 text-left rounded shadow-lg w-full lg:w-64 hidden">
				<li class="p-2 hover:text-teal-400"><a href="#submenu">Submenu 1</a></li>
			</ul>
		</li>
	</menu>
</nav>
`},
				{name:`tcontainer`,description:`<div class="container sm:mx-auto px-4 block flex flex-wrap mt-4"></div>`},
				{name:`tcol3`,description:`<div class="container sm:mx-auto px-4 block flex flex-wrap col3 mt-4">\n\t<div class="w-full md:w-1/2 lg:w-1/3 px-2 mb-4"><div class="border">COL 1</div></div>\n\t<div class="w-full md:w-1/2 lg:w-1/3 px-2 mb-4"><div class="border">COL 2</div></div>\n\t<div class="w-full md:w-1/2 lg:w-1/3 px-2 mb-4"><div class="border">COL 3</div></div>\n</div>`},
				{name:`tcard`,description:`<div class="card w-full md:max-w-sm rounded overflow-hidden shadow-lg">\n\t<img class="w-full" src="https://tailwindcss.com/img/card-top.jpg" alt="Sunset in the mountains">\n\t<div class="px-6 py-4">\n\t\t<div class="font-bold text-xl mb-2">The Coldest Sunset</div>\n\t\t<p class="text-gray-700 text-base">\n\t\t\tLorem ipsum dolor sit amet, consectetur adipisicing elit. Voluptatibus quia, nulla! Maiores et perferendis eaque, exercitationem praesentium nihil.\n\t\t</p>\n\t</div>\n\t<div class="px-6 py-4">\n\t\t<span class="inline-block bg-gray-200 rounded-full px-3 py-1 text-sm font-semibold text-gray-700 mr-2">#photography</span>\n\t\t<span class="inline-block bg-gray-200 rounded-full px-3 py-1 text-sm font-semibold text-gray-700 mr-2">#travel</span>\n\t\t<span class="inline-block bg-gray-200 rounded-full px-3 py-1 text-sm font-semibold text-gray-700">#winter</span>\n\t</div>\n</div>`},
				{name:`tfooter`,description:`<footer class="footertail flex items-center justify-between flex-wrap bg-blue-900 p-6">\n\t<div class="w-full block flex-grow lg:flex lg:items-center lg:w-auto text-center">\n\t\t<div class="text-sm lg:flex-grow">\n\t\t\t<a href="#responsive-header" class="block mt-4 lg:inline-block lg:mt-0 text-teal-200 hover:text-white mr-4">Docs</a>\n\t\t\t<a href="#responsive-header" class="block mt-4 lg:inline-block lg:mt-0 text-teal-200 hover:text-white mr-4">Examples</a>\n\t\t\t<a href="#responsive-header" class="block mt-4 lg:inline-block lg:mt-0 text-teal-200 hover:text-white">Blog</a>\n\t\t</div>\n\t</div>\n</footer>`}
			]
			var keysAllowed = Object.keys(myList);

			var myCompleter = {
				identifierRegexps: [/[^\s]+/],
				getCompletions: function(editor, session, pos, prefix, callback) {
					callback(
						null,
						myList.map(function(table) {
							return {
								caption: table.name,
								value: table.description,
								meta: "Table"
							};
						}));
				}
			};
			self.langTools.addCompleter(myCompleter);


			entries(PROPS).forEach(
				function(propPair) {
					var propName = propPair[0],
						prop = propPair[1],
						value = self.$props[propName];
					if (value !== undefined || prop.hasOwnProperty('v')) {
						self.setOption(propName, value === undefined ? prop.v : value);
					}
				}
			);

			self.editor.on('change', function(e) {
				self.lastValue = self.editor.getValue();
				if (self.allowInputEvent) {
					emit(self, 'input', self.lastValue);
				}
			});

			INPUT_EVENTS.forEach(
				function(eName) {
					self.editor.textInput.getElement().addEventListener(
						eName, function(e) { emit(self, eName, e); }
					);
				}
			);

			EDITOR_EVENTS.forEach(function(eName) {
				self.editor.on(eName, function(e) { emit(self, eName, e); });
			});

			var session = self.editor.getSession();
			session.on('changeAnnotation', function() {

				var annotations = session.getAnnotations(),
					errors = annotations.filter(function(a) { return a.type === 'error'; }),
					warnings = annotations.filter(function(a) { return a.type === 'warning'; });

				emit(self, 'changeAnnotation', {
					type: 'changeAnnotation',
					annotations: annotations,
					errors: errors,
					warnings: warnings
				});

				if (errors.length) {
					emit(self, 'error', { type: 'error', annotations: errors });
				}
				else if (self.isShowingError) {
					emit(self, 'errorsRemoved', { type: 'errorsRemoved' });
				}
				self.isShowingError = !!errors.length;

				if (warnings.length) {
					emit(self, 'warning', { type: 'warning', annotations: warnings });
				}
				else if (self.isShowingWarning) {
					emit(self, 'warningsRemoved', { type: 'warningsRemoved' });
				}
				self.isShowingWarning = !!warnings.length;
				self.editor.clearSelection();
			});
		}
	});
})();
/* END: <ace-editor> Vue component */
