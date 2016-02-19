require.config({
	baseUrl: 'js/app',
	paths: {
		"jquery": "../jquery.min",
		"treeview": "../treeview/treeview",
		"angular": "angular"
	},
	shim: {
		'treeview': {
			deps: ['jquery'],
			exports: 'treeView'
		},
		angular : {
			"exports" : "angular"
		}
	}
});

