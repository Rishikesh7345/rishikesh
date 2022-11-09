require(
    [
        'jquery',
        'Magento_Ui/js/modal/modal',
        'mage/url',
        'Magento_Catalog/js/price-utils'
    ],
    function ($, modal, url, priceUtils) {
        var options = {
            type: 'popup',
            responsive: true,
            modalClass: 'build-box',
            innerScroll: true,
            buttons: [{
                text: $.mage.__('Close'),
                class: 'modal-close',
                click: function () {
                    this.closeModal();
                }
            }]
        };
        var minProQty = '';
        var productLogoQty = 250;
        modal(options, $('#modal-content'));
        $("#modal-btn").on('click', function () {
            $(".build_box_step_1").addClass("active");
            $(".del_popup").find("#continue-button").removeClass("editContinueBtn");
            $(".del_popup").find("#continue-button").removeClass("editContName");
            $(".del_popup").find("#continue-button").addClass("continue-button");
           
            $(".active_buildbox").show();
            $(".modal-inner-wrap").show();
            $("#modal-content").modal("openModal");
        });

        $(".skip-btn2").click(function () {
            $(".modal-popup").hide();
            location.reload();
        });

		$('#input-box-name').keypress(function (e) {
			var regex = new RegExp("^[a-zA-Z ]*$");
			var str = String.fromCharCode(!e.charCode ? e.which : e.charCode);
			if (regex.test(str)) {
				$(".next-btn").on('click', function () {
					var boxName = $(".input-box-name").val();
					if (boxName == "") {
						$(".input-box-name").css("border", "2px solid red");
						$(".next-btn").attr('disabled', false);
					} else {
						$(".box-title-name").text(boxName);
						$(".build_box_step_1").hide();
						$(".build_box_step_2").show();
					}
				});
				$("#invaildName").hide();
				return true;
			}
			else
			{
				e.preventDefault();
				$(".next-btn").off('click');
				$("#invaildName").show();
				return false;
			}
		});
		       
        $(".next-btn2").on('click', function (e) {
            var proDim = [];
            var cartProduId = '';
            var prodQty = '';
            var checkNo = '';
            var proQty = [];
            $(".build_box_step_2").find('.product-list').each(function () {
                //get values
                if ($(this).find('.proDimVal').prop('checked') == true) {                    
                    let productQty = $(this).find(".product-qty").val();
                    cartProduId = $(this).find('.proDimVal').attr('data-pro-id');
                    var proDimVal = $(this).find('.proDimVal').attr('data-dim');
                    
                    proQty.push($(this).find(".product-qty").val());
                   
                    prodQty = $(this).find('.proDimVal').attr('prod-qty');
                    $("#errMsg").hide();
                    checkNo = 1;
                    proDim.push(proDimVal);
                }
                else {                    
                    checkNo = $(".build_box_step_2").find('input[type="checkbox"]:checked').length;
                    if (checkNo == 0) {
                        $("#errMsg").show();
                    }
                }
            });
            minProQty = Math.min.apply(Math,proQty);
            $("#boxQty").val(minProQty);
            if (checkNo != 0) {
                $.ajax({
                    type: "post",
                    url: url.build('buildbox/submit/dimensionvalue'),
                    data: ({
                        proDim: proDim,
                        cartProduId: cartProduId,
                        prodQty: prodQty
                    }),
                    cache: false,
                    showLoader: true,
                    success: function (data) {
                        $("#productBox").html(data);
                        $(".build_box_step_2").hide();
                        $(".build_box_step_3").show();
                    },
                    error: function (xhr, status, error) {
                        console.error(xhr);
                    }
                });
            }
            e.preventDefault();
        });

        $("#editBoxQty").keyup(function(){ 
			 
            var boxQty = $(this).val();
            if(parseInt(boxQty) >= productLogoQty){
               $("#respProDetails").find(".additional:nth-child(4)").show();
               $("#editRespOption").find(".additional:nth-child(4)").show();
            }else{
                $("#respProDetails").find(".additional:nth-child(4)").hide();
                $("#editRespOption").find(".additional:nth-child(4)").hide();
            }
            if($(this).val() <= editMinQty){
                $("#editQtyErr").css("color","red");
                $("#editMinQty").html(editMinQty);
                $("#editQtyErr").show();
                $("#save-change").prop('disabled', true);
            }
            else{
                $("#save-change").prop('disabled', false);
                $("#editQtyErr").hide();
            }
        });

        $('#productBox').on('click', '.product-box', function () {
                 
            const checkBuildbox = $(this).find("input[name='choose-buildbox']");
            checkBuildbox.prop("checked", true);
            $("#productBox").find("input[name='choose-buildbox']").each(function(){
                $(this).parent(".product-box").removeClass("selected");
            });
            if(checkBuildbox.prop('checked') == true){
               $(this).addClass("selected");
              
              
            }
            $('.build_box_step_3').find("#boxErrMsg").hide();
            const boxParentId = $(this).find("input[name='box_parent_Id']").val();
      
            $("#errMsg1").hide();
            $(".boxEdit").find("#editErrMsg-1").hide();
            
            $.ajax({
                type: "POST",
                url: url.build('buildbox/submit/proid'),
                data: {
                    boxId: $("input[name='choose-buildbox']:checked").val(),
                    boxParentId: boxParentId
                },
                cache: false,
                dataType: 'html',
                showLoader: true,
                success: function (data) {
                    $("#respProId").html(data);
                    getFileVal2();
                },
                error: function (xhr, status, errorThrown) {
                    console.log('Error happens. Try again.');
                }
            });
        });
        var countBoxPro = 0;
        var countEditBoxPro = 0;
        //click box than select radio button
        $(".product-list").on('click', function (e) {
            var checkbox = $(this).children('input[type="checkbox"]');
            checkbox.prop('checked', !checkbox.prop('checked'));

            if (checkbox.prop('checked') == true) {
                $(this).addClass("selected");

                countBoxPro += checkbox.filter(':checked').length;
                countEditBoxPro += checkbox.filter(':checked').length;
                var qtyEachBox = $(this).find(".qtyEachBox").val(2);
                $("#errMsg").css("display", "none");
                $("#editErrMsg-1").hide();
                qtyEachBox = $(this).find("#qtyEachBox").val();
                boxQty = $("#boxQty").val();
                prodCartQty = $(this).find("#prodCartQty").val();
                totQty = qtyEachBox * boxQty;
                res = prodCartQty - totQty;
                if (res <= 0) {
                    $(this).find("#prodCartQty").val(0);
                    $(this).find("#err").text("you add" + parseInt(res) + "product");
                } else {
                    $(this).find("#prodCartQty").val(res);
                }

            } else {
                $(this).removeClass("selected");
                countBoxPro -= 1;
                countEditBoxPro -=1;
                $(this).find(".qtyEachBox").val(0);
                var prodCartQtyHid = $(this).find("#prodCartQtyHid").val();
                $(this).find("#prodCartQty").val(prodCartQtyHid);
            }
            $(".pro-select").html(countBoxPro);
            $(".edit_build_box_1").find(".pro-select").html(countEditBoxPro);
            $("#count-product-box").html(`Selected (${countBoxPro}) product In a box`);
            $("#countProduct").html(`Selected (${countBoxPro}) product In a box`);
            e.preventDefault();
        });

        $(".pre-btn2").on('click', function () {
            $(".build_box_step_2").hide();
            $(".build_box_step_1").show();
        });

        $(".next-btn3").on('click', function () {
            var isValid = $("input[name='choose-buildbox']").is(":checked");
            var boxQty = $("#boxQty").val();
            if (isValid == true) {
                $(".build_box_step_3").hide();
                $("boxErrMsg").css("display", "none");
                $(".build_box_step_4").show();
                var box_price = $("#box_price").val()
                var totPrice = box_price * boxQty;
                $("#parPrice").text(box_price);
                $("#totPrice").text(totPrice);
            }
            else {
                $(".next-btn3").attr('disabled', false);
                $("#boxErrMsg").css("display", "block");
                // $("#colorError")[0].style.display = isValid ? "none" : "block";
            }
        });
        $(".pre-btn3").on('click', function () {
            $("#respOption").html("");
            $("#respProId").html("");
            $("#editRespOption").html('');
            $(".build_box_step_3").hide();
            $(".build_box_step_2").show();
        });
        $(".pre-btn4").on('click', function () {
            $("#respOption").html("");
            $("#respProId").html("");
            $(".build_box_step_4").hide();
            $(".build_box_step_3").show();
        });

        $(".pre-btn5").on('click', function () {
            $(".build_box_step_4").show();
            $(".build_box_step_5").hide();
        });
        $(".next-btn4").on('click', function () {
            $(".build_box_step_4").hide();
            $(".build_box_step_5").show();
        });

        
        $(".boxQty").keyup(function (e) {
            var qtyValue = $(this).val();
            var inputQty = ($("#inputProductQty").attr("inputProductQty"));
            if(parseInt(qtyValue) > parseInt(inputQty)){
               
                $("#qtyResErr").css({"color":"red","display":"block"});
                $("#qtyResErr").html("The requested qty exceeds the maximum qty allowed in shopping cart");
                $(".submit_button").find("#submit").prop('disabled', true); 
            }else{
                $(".submit_button").find("#submit").prop('disabled', false);
                $("#qtyResErr").css("display","none");
            }
            var boxQty = $(this).val();
            if(parseInt(boxQty) >= productLogoQty){
               $("#respProId").find(".additional:nth-child(4)").show();
               $("#respOption").find(".additional:nth-child(4)").show();
            }else{
                $("#respProId").find(".additional:nth-child(4)").hide();
                $("#respOption").find(".additional:nth-child(4)").hide();
            }
            
            if ($(this).val() < minProQty) {
                $("#minQty").html(minProQty);
                $("#QtyErr").show();

                $("#submit").prop('disabled', true);
            } else {
                $("#QtyErr").hide();
                $("#submit").prop('disabled', false);
                var box_price = $("#box_price").val();
                $("#per_box_price").val(box_price);
                var getBoxQty = url.build('buildbox/submit/getboxqty');
                var producQty = $('.proDimVal').attr('prod-qty');               
            }
            e.preventDefault(e);
        });

        $("#submit").click(function (e) {
            e.preventDefault(e);
            var isValid = $("#respProId").find("input[name='child_product']");
            if (isValid.is(":checked") != true) {
                $(".submit_button").find("#selectError1").show();
            } else {
                var form = $('#build_box_form')[0];
                var data = new FormData(form);
                $("#submit").prop("disabled", true);
                $.ajax({
                    enctype: 'multipart/form-data',
                    type: "POST",
                    url: url.build('buildbox/submit/index'),
                    data: data,
                    processData: false,
                    contentType: false,
                    cache: false,
                    dataType: 'json',
                    showLoader: true,

                    success: function (data) {
                        if (data.success == "true") {
                            $("#submit").prop("disabled", false);
                            location.reload();                            
                            hideOptionFun();
                        }
                    },
                    error: function (xhr, status, errorThrown) {
                        console.log('Error happens. Try again.');
                        $("#submit").prop("disabled", false);
                    }
                });
            }
            if (isValid.length == 0) {
                $(".submit_button").find("#selectError1").hide();
                var form = $('#build_box_form')[0];
                var data = new FormData(form);
                $("#submit").prop("disabled", true);
                $.ajax({
                    enctype: 'multipart/form-data',
                    type: "POST",
                    url: url.build('buildbox/submit/index'),
                    data: data,
                    processData: false,
                    contentType: false,
                    cache: false,
                    dataType: 'json',
                    showLoader: true,

                    success: function (data) {
                        if (data.success == "true") {
                            location.reload();                             
                            $("#submit").prop("disabled", false);
                        }
                    },
                    error: function (xhr, status, errorThrown) {
                        console.log('Error happens. Try again.');
                        $("#submit").prop("disabled", false);
                    }
                });
            }
        });
        $('#respProId').on('change', '.childProOption', function () {
            isValid = $("#respProId").find("input[name='child_product']").is(":checked");
            if (isValid == true) {
                $("#submit").prop("disabled", false);
                $(".submit_button").find("#selectError1").hide();
                let childOpId = $(this).val();
                $("#respOption").html('');
                $.ajax({
                    type: "POST",
                    url: url.build('buildbox/submit/getproductoption'),
                    data: {
                        childOpId: childOpId
                    },
                    cache: false,
                    dataType: 'html',
                    showLoader: true,
                    success: function (data) {
                        $("#respOption").html(data);
                        getFileVal();
                        $("#submit").prop('disabled', false);
                    },
                    error: function (xhr, status, errorThrown) {
                        console.log('Error happens. Try again.');
                    }
                });
            }
        });
        //name Edit
        modal(options, $('.boxNameEdit'));
    
        $(".editName").each(function (){
			$(this).click(function (e) {
				$(this).hover();
				$(".del_popup").find("#continue-button").removeClass("editContinueBtn");
				$(".del_popup").find("#continue-button").removeClass("continue-button");
				$(".del_popup").find("#continue-button").addClass("editContName");
				
				let boxname = $(this).find(".edit-box-name-proid").attr("boxTitle");
				let proid = $(this).find(".edit-box-name-proid").val();
				let prodItemId = $(this).find(".edit-box-name-proid").attr("prodItemId");
				$(".edit-box-name").val(boxname);
				$(".edit-proid").val(proid);
				$(".edit-prodItemId").val(prodItemId);  
				$(".boxNameEdit").modal("openModal");
				e.preventDefault(e);
			});	
		});
        $("#build_box_edit").submit(function (e) {
            let nameEditProdUrl = url.build('buildbox/boxedit/boxnameedit');
            $.ajax({
                type: "POST",
                url: nameEditProdUrl,
                data: $("#build_box_edit").serialize(),
                cache: false,
                dataType: 'json',
                processData: false,
                showLoader: true,
                success: function (data) {
                    if (data.success == "true") {
                        location.reload();
                    }
                },
                error: function (xhr, status, errorThrown) {
                    console.log('Error happens. Try again.');
                }
            });
            e.preventDefault(e);
        });

       /**
         * Edit box script
         */
        var editProId = '';
        var editMinQty = '';
        modal(options, $('#edit-box'));
        $(".box-edit").on('click', function (e) {
            var editProId = $(this).find(".editProId").val();
            var proditemid = $(this).find(".editProId").attr("proditemid");
            var boxId = $(this).find(".boxId").val();
            var boxDimentions = $(this).find(".boxDimentions").val();
            editProId = $(this).find(".editProId").val();
            editItemId = $(this).find(".editProId").attr("prodItemId");
            $(".existId").val(editProId);
            $(".editItemId").val(editItemId);
            var boxName = $(this).find(".boxName").val();
            var boxDimen = $(this).find(".boxDimentions").val();
            var boxId = $(this).find(".boxId").val();
            var prodQty = $(this).find(".boxId").attr("editProQty");
            $(".existBoxName").val(boxName);

            $.ajax({
                type: "post",
                url: url.build('buildbox/boxedit/editproid'),
                data: ({
                    editProId: editProId,
                    proditemid: proditemid,
                    boxId: boxId,
                    boxDimentions: boxDimentions
                }),
                cache: false,
                showLoader: true,
                success: function (data) {
            
                    $("#edit-box").modal("openModal");
                    $("#editRespSection").html(data);
                   
                    $(".edit_build_box_1").addClass("active");
                     $(".del_popup").find("#continue-button").removeClass("continue-button");
                    $(".del_popup").find("#continue-button").removeClass("editContName");
                    $(".del_popup").find("#continue-button").addClass("editContinueBtn");
                   				
                    editRespsection();
                },
                error: function (xhr, status, error) {
                    console.error(xhr);
                }
            });
        });

        $(".edit-next1").on('click', function (e) {
            var proDim = [];
            var prodQty = '';
            var editProQty = [];
            $("#editRespSection").find('.product-list').each(function () {
                //get values
                cartProduId = '';
                if ($(this).find('.proDimVal').prop('checked') == true) {  

                    let productQty = $(this).find("#prod-qty").val();
                    editProQty.push(productQty);
                    cartProduId = $(this).find('.proDimVal').attr('data-pro-id');
                    var proDimVal = $(this).find('.proDimVal').attr('data-dim');
                    prodQty = $(this).find('.proDimVal').attr('prod-qty');

                    $(".boxEdit").find("#editErrMsg-1").hide();
                    checkNo = 1;
                    proDim.push(proDimVal);

                }
                else {
                    checkNo = $(".edit_build_box_1").find('input[type="checkbox"]:checked').length;
                    if (checkNo == 0) {
                        $(".boxEdit").find("#editErrMsg-1").show();
                    }
                }
            });
            editMinQty = Math.min.apply(Math,editProQty);
            $("#editBoxQty").val(editMinQty);
            if (checkNo != 0) {
                $.ajax({
                    type: "post",
                    url: url.build('buildbox/submit/dimensionvalue'),
                    data: ({
                        proDim: proDim,
                        cartProduId: cartProduId,
                        prodQty: prodQty
                    }),
                    cache: false,
                    showLoader: true,
                    success: function (data) {
                        $("#editResponse").html(data);
                        $(".edit_build_box_1").hide();
                        $(".edit_build_box_2").show();
                    },
                    error: function (xhr, status, error) {
                        console.error(xhr);
                    }
                });
            }
            e.preventDefault();
        });
        function editRespsection(){
			 $(".edit_build_box").find(".pro-select").html($('input:checkbox:checked').length);
			var countBoxPro = $('input:checkbox:checked').length;
			var countEditBoxPro = $('input:checkbox:checked').length;
			$(".edit_build_box").find("#countProduct").html(`Selected (${countBoxPro}) product In a box`);
			//click box than select radio button
			 $("#editRespSection").find(".product-list").on('click', function (e) {
				var checkbox = $(this).children('input[type="checkbox"]');
				checkbox.prop('checked', !checkbox.prop('checked'));

				if (checkbox.prop('checked') == true) {
					$(this).addClass("selected");

					countBoxPro += checkbox.filter(':checked').length;
					countEditBoxPro += checkbox.filter(':checked').length;
      
					var qtyEachBox = $(this).find(".qtyEachBox").val(2);
					$("#errMsg").css("display", "none");
					$("#editErrMsg-1").hide();
					qtyEachBox = $(this).find("#qtyEachBox").val();
					boxQty = $("#boxQty").val();
					prodCartQty = $(this).find("#prodCartQty").val();
					totQty = qtyEachBox * boxQty;
					res = prodCartQty - totQty;
					if (res <= 0) {
						$(this).find("#prodCartQty").val(0);
						$(this).find("#err").text("you add" + parseInt(res) + "product");
					} else {
						$(this).find("#prodCartQty").val(res);
					}

				} else {
					$(this).removeClass("selected");
					if(countBoxPro == 1){
					  countBoxPro -= 1;
					  countEditBoxPro -=1;
				    }else{
					  countBoxPro -= 1;
					  countEditBoxPro -=1;
					}
					$(this).find(".qtyEachBox").val(0);
					var prodCartQtyHid = $(this).find("#prodCartQtyHid").val();
					$(this).find("#prodCartQty").val(prodCartQtyHid);
				}
				
				$(".pro-select").html(countBoxPro);
				$(".edit_build_box_1").find(".pro-select").html(countEditBoxPro);
				$("#count-product-box").html(`Selected (${countBoxPro}) product In a box`);
				$(".edit_build_box").find("#countProduct").html(`Selected (${countBoxPro}) product In a box`);
				e.preventDefault();
			});
			
		}
        $('#editResponse').on('click', '.product-box', function () {
            const checkBuildbox = $(this).find("input[name='choose-buildbox']");

            checkBuildbox.prop("checked", true);
            $("#editResponse").find("input[name='choose-buildbox']").each(function(){
                $(this).parent(".product-box").removeClass("selected");
            });
            if(checkBuildbox.prop('checked') == true){
               $(this).addClass("selected");
            }

            const submitProdUrl = url.build('buildbox/submit/proid');
            const boxParentId = $(this).find("input[name='box_parent_Id']").val();
            $("#editBoxErrMsg").css("display", "none");
            $.ajax({
                type: "POST",
                url: submitProdUrl,
                data: {
                    boxId: $("input[name='choose-buildbox']:checked").val(),
                    boxParentId: boxParentId
                },
                cache: false,
                dataType: 'html',
                showLoader: true,
                success: function (data) {
                    $("#respProDetails").html(data);
                    getEditFileVal2();
                },
                error: function (xhr, status, errorThrown) {
                    console.log('Error happens. Try again.');
                }
            });
        });

        $(".edit-next2").click(function () {
            var isValid = $("#editResponse").find("input[name='choose-buildbox']").is(":checked");
            if (isValid == true) {
                $(".edit_build_box_2").hide();
                $("#editBoxErrMsg").css("display", "none");
                $(".edit_build_box_3").show();
                var box_price = $("#box_price").val();
                var editBoxQty = $("#editBoxQty").val();
                var totPrice = box_price * editBoxQty;
                $("#editparPrice").text(box_price);
                $("#editPrice").text(totPrice);
            }
            else {
                $(".next-btn3").attr('disabled', false);
                $("#editBoxErrMsg").css("display", "block");
                $("#colorError")[0].style.display = isValid ? "none" : "block";
            }
        })
        $("#save-change").click(function (e) {
            e.preventDefault(e);
            var isValid = $("#respProDetails").find("input[name='child_product']");
            if (isValid.length != 0) {
                if (isValid.is(":checked") != true) {
                    $(".edit_submit_button").find("#selectError").show();
                } else {
                    var form = $('#editBoxProdct')[0];
                    $(".edit_submit_button").find("#selectError").show();
                    // Create an FormData object 
                    var data = new FormData(form);
                    $("#submit").prop("disabled", true);
                    $.ajax({
                        enctype: 'multipart/form-data',
                        type: "POST",
                        url: url.build('buildbox/boxedit/editboxsave'),
                        data: data,
                        processData: false,
                        contentType: false,
                        cache: false,
                        dataType: 'json',
                        showLoader: true,
                        success: function (data) {
                            if (data.success == "true") {
                                location.reload();
                                $("#submit").prop("disabled", true);
                            }
                        },
                        error: function (xhr, status, errorThrown) {
                            console.log('Error happens. Try again.');
                            $("#submit").prop("disabled", false);
                        }
                    });
                }
            } else {
                $(".edit_submit_button").find("#selectError").hide();
                var form = $('#editBoxProdct')[0];
                $(".edit_submit_button").find("#selectError").show();
                // Create an FormData object 
                var data = new FormData(form);
            
                $("#submit").prop("disabled", true);
                $.ajax({
                    enctype: 'multipart/form-data',
                    type: "POST",
                    url: url.build('buildbox/boxedit/editboxsave'),
                    data: data,
                    processData: false,
                    contentType: false,
                    cache: false,
                    dataType: 'json',
                    showLoader: true,
                    success: function (data) {
                        if (data.success == "true") {
                            location.reload();
                            $("#submit").prop("disabled", true);
                        }
                    },
                    error: function (xhr, status, errorThrown) {
                        console.log('Error happens. Try again.');
                        $("#submit").prop("disabled", false);
                    }
                });
            }
        });

        $(".edit_build_box_2").find(".edit-pre1").click(function () {
            $("#editBoxProdct").find(".edit_build_box_2").hide();
            $("#editBoxProdct").find(".edit_build_box_1").show();
        });
        $(".edit_build_box_3").find(".pre-btn3").click(function () {
            $("#editBoxProdct").find(".edit_build_box_3").hide();
            $("#editBoxProdct").find(".edit_build_box_2").show();
        });

        $('#respProDetails').on('change', '.childProOption', function () {
            let childOpId = $(this).val();
            $(".edit_submit_button").find("#selectError").hide();
            $("#editRespOption").html('');
            $.ajax({               
                type: "POST",
                url: url.build('buildbox/submit/getproductoption'),
                data: {
                    childOpId: childOpId
                },
                cache: false,
                dataType: 'html',
                showLoader: true,
                success: function (data) {
                    $("#editRespOption").html(data);
                    getEditFileVal();
                    $("#submit").prop('disabled', false);
                },
                error: function (xhr, status, errorThrown) {
                    console.log('Error happens. Try again.');
                }
            });
        });
        $(".action-close").click(function(){
            location.reload();
        });
               
        function getFileVal(){    
            var productBoxQty = $("#boxQty").val();
            
            if(productBoxQty <= productLogoQty){
               $(".addons-section").find(".additional:nth-child(4)").hide();
            }       
            $("#respOption").find('input[type="file"]').change(function(e){
				
				$(this).next('[name="radioSelect"]').prop("checked", false);
				var a = $(this).next('[name="radioSelect"]');
				a.prop("checked", true);
				var parentClass = $(this).parent().attr("class");
				
				$("#respOption").find("."+parentClass).each(function(){
					if($(this).find('[name="radioSelect"]').is(':checked')==false){
						$(this).find('input[type="file"]').val("");
						
					}else{
					  var fileName = e.target.files[0].name;
						var allowedExtensions =/(\.jpg|\.png|\.gif)$/i;

						if (!allowedExtensions.exec(fileName)) {
							$("#submit").prop('disabled', true);
							$(this).next('[name="radioSelect"]').prop("checked", false);
							$("#fileErr").show();
							fileName.value = '';
							return false;
						}else{
							$("#fileErr").hide();
							$("#submit").prop('disabled', false);
						}
					}
				});
            });
        }

        function getFileVal2(){
             var productBoxQty = $("#boxQty").val();
            
            if(productBoxQty <= productLogoQty){
               $(".addons-section").find(".additional:nth-child(4)").hide();
            }
            $("#respProId").find('input[type="file"]').change(function(e){
				
				$(this).next('[name="radioSelect"]').prop("checked", false);
				var a = $(this).next('[name="radioSelect"]');
				a.prop("checked", true);
				var parentClass = $(this).parent().attr("class");
							
				$("#respProId").find("."+parentClass).each(function(){
					if ($(this).find('[name="radioSelect"]').is(':checked')==false){
						
						$(this).find('input[type="file"]').val("");
					} else {
                       $option_id = $(this).find('input[type="file"]').attr("optionId");
                       $("#option_id").val($option_id);
						var fileName = e.target.files[0].name;
						var allowedExtensions =/(\.jpg|\.png|\.gif)$/i;
                       
						if (!allowedExtensions.exec(fileName)) {
							$("#submit").prop('disabled', true);
							$(this).next('[name="radioSelect"]').prop("checked", false);
							$("#fileErr").show();
							fileName.value = '';
							return false;
						}else{

							$("#fileErr").hide();
							$("#submit").prop('disabled', false);
						}
					}
				});
            });
        }
        
        function getEditFileVal2(){
                var productBoxQty = $("#editBoxQty").val();
                if(productBoxQty <= productLogoQty){
                  $("#respProDetails").find(".additional:nth-child(4)").hide();
                }

				$("#editFileErr").hide();
				$("#respProDetails").find('input[type="file"]').change(function(e){
					$(this).next('[name="radioSelect"]').prop("checked", false);
					var a = $(this).next('[name="radioSelect"]');					
					a.prop("checked", true);
					var parentClass = $(this).parent().attr("class");
					
					$("#respProDetails").find("."+parentClass).each(function(){
						if($(this).find('[name="radioSelect"]').is(':checked')==false){
							
							$(this).find('input[type="file"]').val("");
						} else {

							var fileName = e.target.files[0].name;
							var allowedExtensions =/(\.jpg|\.png|\.gif)$/i;
							if (!allowedExtensions.exec(fileName)) {

								$("#save-change").prop('disabled', true);
								$(this).next('[name="radioSelect"]').prop("checked", false);
								$("#editFileErr").show();
								fileName.value = '';								
								return false;
							}else{
                                
								$("#editFileErr").hide();
								$("#save-change").prop('disabled', false);
							}
						}
					});
                
				});
		}
		
		function getEditFileVal(){
            var productBoxQty = $("#editBoxQty").val();
            if(productBoxQty <= productLogoQty){
               $("#editRespOption").find(".additional:nth-child(4)").hide();
            }
				$("#editRespOption").find('input[type="file"]').change(function(e){
					
					$(this).next('[name="radioSelect"]').prop("checked", false);
					var a = $(this).next('[name="radioSelect"]');
					a.prop("checked", true);
					var parentClass = $(this).parent().attr("class");
					
					$("#editRespOption").find("."+parentClass).each(function(){
						
						if($(this).find('[name="radioSelect"]').is(':checked')==false){
							
							$(this).find('input[type="file"]').val("");							
						} else {
							
							var fileName = e.target.files[0].name;
							var allowedExtensions =/(\.jpg|\.png|\.gif)$/i;
							if (!allowedExtensions.exec(fileName)) {
								
								$("#save-change").prop('disabled', true);
								$(this).next('[name="radioSelect"]').prop("checked", false);
								$("#editFileErr").show();
								fileName.value = '';								
								return false;
							} else {
								
								$("#editFileErr").hide();
								$("#save-change").prop('disabled', false);
							}
						}
					});
                
				});
		}
		
		$("#esdcclick").click(function(){
			$(".esdcinfo-show").toggle();
		 });
         function hideOptionFun(){
         $('.item-options').find("dt").each(function(){
            if($(this).html() == 'unique_box_id'){
                $(this).addClass("abc");
                $(this).next().addClass("abc2");
                $(this).hide();
                $(this).next().hide();
            }
        })
      }
    }
);
