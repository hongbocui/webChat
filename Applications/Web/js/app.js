require(['jquery', 'treeview','angular'],function($, treeview,angular){
	angular.module("Chat", [])
		.controller("contact",function($scope, $http){
            $http.get("chatapi.php?c=user&a=allusers").success(function(result) {
				    $scope.structure = result.data;
            });
		})
		.directive('treeView', [function(){
			return {
				restrict: 'E',
		   		templateUrl: './treeview.html',
		   		scope: {
					treeData: '=',
		  		},
		   		controller: function($scope) {
					$scope.isFolder = function(item){
						for(var x in item){
							if(typeof(item[x]) == 'object')
			   					return true;
						}
						return false;
					};
					$scope.isFiles = function(item){
						return typeof(item) == 'object';
					};
				},
                compile: function(element, attrs, transclude) {
                    $(element).treeView({'defaultOpen':0});
                }
			}
		   }]);
	//使用require需要手动启动angular
	angular.bootstrap(document,['Chat']);
})
