//Initialize variables
var usersData = '';
var groupsData = '';
/**
 * trimStr
 * Trim a string for internet explorer
 *
 * @param str (String)
 * @return (String)
 *
 * by Telmo Chiri - telmo.chiri@processmaker.com
 */
function trimStr(str) {
    return typeof str != "undefined" ? str.replace(/^\s+|\s+$/g, '') : str;
}


/**
 * populateConfiguration
 * Allows populate all the information related to the output document
 *
 * @param none
 * @return none
 *
 * by Telmo Chiri - telmo.chiri@processmaker.com
 */
function populateConfiguration() {
    //Get the configuration
    $.ajax({
        'url': '../psAzureAD/controllers/azureADAjax.php',
        'type': 'GET',
        'data': {
            'option': 'getConfiguration'
        },
        beforeSend: function () {
            $.LoadingOverlay("show",{
                imageColor: "#2378D4",
                text: "Loading..."
            });
        },
        success: function (response) {
            var aResponse = JSON.parse(response);
            if (aResponse.success) {
                if (aResponse.data.PAADC_ID) {
                    $('#configId').val(aResponse.data.PAADC_ID);
                    $('#tenantId').val(aResponse.data.TENANT_ID);
                    $('#clientId').val(aResponse.data.CLIENT_ID);
                    $('#clientSecret').val(aResponse.data.CLIENT_SECRET);
                }
            }
        },
        complete: function () {
            $.LoadingOverlay("hide");
        }
    });
}


/**
 * addClipboardTitle
 * Allows add new message in the tooltip button (btnCopyJSCode)
 *
 * @param none
 * @return none
 *
 * by Telmo Chiri - telmo.chiri@processmaker.com
 */
function addTooltipOutput() {
    $('#tooltipOutput').prop('title', "Records marked with * have previous configuration.");
    $('#tooltipOutput').tooltip('show');
    $('#tooltipOutput').prop('title', "");
}

/**
 * saveConfiguration
 * Save the configuration
 *
 * @param none
 * @return none
 *
 * by Telmo Chiri - telmo.chiri@processmaker.com
 */
function saveConfiguration() {
    var configId = trimStr($('#configId').val());
    var tenantId = trimStr($('#tenantId').val());
    var clientId = trimStr($('#clientId').val());
    var clientSecret = trimStr($('#clientSecret').val());
    if (tenantId && clientId && clientSecret) {
        $.ajax({
            'method': 'POST',
            'url': '../psAzureAD/controllers/azureADAjax.php',
            'data': {
                'option': 'saveConfiguration',
                'configId': configId,
                'tenantId': tenantId,
                'clientId': clientId,
                'clientSecret': clientSecret
            },
            beforeSend: function () {
                $.LoadingOverlay("show",{
                    imageColor: "#2378D4",
                    text: "Saving..."
                });
            },
            success: function (response) {
                var aResponse = JSON.parse(response);
                if (aResponse.success)
                    bootbox.alert('<h5 class="text-info">Success</h5><br>'+aResponse.data, function () {
                        populateConfiguration()
                    });
                else
                    bootbox.alert('<h5 class="text-danger">Error</h5><br>'+aResponse.data, function () {
                        populateConfiguration()
                    });
            },
            complete: function () {
                $.LoadingOverlay("hide");
            }
        });
    } else {
        bootbox.alert("Please fill out all the required fields.");
    }
}

/**
 * generateAppAccessToken
 * Save the configuration
 *
 * @param none
 * @return none
 *
 * by Telmo Chiri - telmo.chiri@processmaker.com
 */
function generateAppAccessToken() {
    var configId = trimStr($('#configId').val());
    if (configId) {
        $.ajax({
            'method': 'POST',
            'url': '../psAzureAD/controllers/azureADAjax.php',
            'data': {
                'option': 'generateAppAccessToken',
                'configId': configId
            },
            success: function (response) {
                var aResponse = JSON.parse(response);
                if (aResponse.success) {
                    bootbox.alert('<h5 class="text-success">Success</h5><br>New Access Token was generated successfully.');
                } else {
                    bootbox.alert('<h5 class="text-danger">'+aResponse.data.error+'</h5><br>'+aResponse.data.error_description);
                }
            }
        });
    } else {
        bootbox.alert("Please fill out all the required fields and Save.");
    }
}

/**
 * connectToAzure
 * Connect to End Points of Azure AD
 *
 * @param option //Option of request
 * @return none
 *
 * by Telmo Chiri - telmo.chiri@processmaker.com
 */
function connectToAzure(option) {
    var configId = trimStr($('#configId').val());
    if (configId) {
        $.ajax({
            'method': 'POST',
            'url': '../psAzureAD/controllers/azureADAjax.php',
            'data': {
                'option': 'getAppAccessToken',
                'configId': configId
            },
            beforeSend: function() {
                $.LoadingOverlay("show",{
                    imageColor: "#2378D4",
                    text: "Loading..."
                });
            },
            success: function (response) {
                var aResponse = JSON.parse(response);
                if (aResponse.success) {
                    switch (option) {
                        case 'getAzureUsers':
                            getAzureUsers();
                            break;
                        case 'getAzureGroups':
                            getAzureGroups();
                            break;
                        default:
                            break;
                    }
                } else {
                    bootbox.alert("Please refresh Token.");
                }
            }
        });
    } else {
        bootbox.alert("<h5>Alert</h5>Please Add the Configurations on the <b>\"Settings\"</b> Panel.");
    }
}

/**
 * getAzureUsers
 * Get All Azure Users
 *
 * @return none
 *
 * by Telmo Chiri - telmo.chiri@processmaker.com
 */
function getAzureUsers() {
    $.ajax({
        'method': 'POST',
        'url': '../psAzureAD/controllers/azureADAjax.php',
        'data': {
            'option': 'getAzureUsers'
        },
        beforeSend: function () {
            $.LoadingOverlay("text", "Get Users...");
        },
        success: function (response) {
            $.LoadingOverlay("text", "Populate Data Table...");
            var aResponse = JSON.parse(response);
            if (aResponse.success) {
                populateUsersDataTable(aResponse.data);
            } else {
                errorConnectToAzure(aResponse.data);
            }
            $.LoadingOverlay("hide");
        }
    });
}

/**
 * getAzureGroups
 * Get All Azure Groups
 *
 * @return none
 *
 * by Telmo Chiri - telmo.chiri@processmaker.com
 */
function getAzureGroups() {
    $.ajax({
        'method': 'POST',
        'url': '../psAzureAD/controllers/azureADAjax.php',
        'data': {
            'option': 'getAzureGroups'
        },
        beforeSend: function () {
            $.LoadingOverlay("text", "Get Groups...");
        },
        success: function (response) {
            $.LoadingOverlay("text", "Populate Data Table...");
            var aResponse = JSON.parse(response);
            if (aResponse.success) {
                populateGroupsDataTable(aResponse.data);
            } else {
                errorConnectToAzure(aResponse.data);
            }
            $.LoadingOverlay("hide");
        }
    });
}

/**
 * errorConnectToAzure
 * Display Error Connect to Azure
 *
 * @param data (object)  // Data response
 * @return none
 *
 * by Telmo Chiri - telmo.chiri@processmaker.com
 */
function errorConnectToAzure(data) {
    if (data.code == 'InvalidAuthenticationToken') {
        bootbox.confirm({
            message: '<h5>' + data.message + '</h5><br>Do you want generate a new token?',
            buttons: {
                cancel: {
                    label: 'Cancel',
                    className: 'btn-secondary'
                },
                confirm: {
                    label: 'Generate Token'
                }
            },
            callback: function (result) {
                if (result) {
                    generateAppAccessToken();
                }
            }
        });
    } else {
        bootbox.alert('<h5 class="text-danger">'+ data.code + '</h5><br>' + data.message);
    }
}

/**
 * populateUsersDataTable
 * Populate Data Table
 *
 * @param data (object)  // Data response
 * @return none
 *
 * by Telmo Chiri - telmo.chiri@processmaker.com
 */
function populateUsersDataTable(aData) {
    usersData = aData;
    $('#userTable').show();
    var table = $('#userTable').DataTable({
        processing: true,
        deferLoading: 57,
        data: aData,
        destroy: true,
        columns: [
            {
                data: 'check',
                render: function(data, type, row) {
                    if (row.mail) {
                        if (row.sync) { //Synced (Disable)
                            return '<input type="checkbox" id="ch-' + row.id + '" checked disabled/>';
                        } else {    //Unsynced
                            if (row.check) {
                                return '<input type="checkbox" id="ch-' + row.id + '" class="userCheckable" checked onclick="checkUser(this, ' + row.key + ')" />';
                            } else {
                                return '<input type="checkbox" id="ch-' + row.id + '" class="userCheckable" onclick="checkUser(this, ' + row.key + ')" />';
                            }
                        }
                    } else {    //No Email
                        return '<input type="checkbox" id="ch-' + row.id + '" onclick="alertEmailNeeded(\'ch-' + row.id + '\')"/>';
                    }
                }
            },
            {
                data: 'givenName'
            },
            {
                data: 'surname'
            },
            {
                data: 'mail'
            },
            {
                data: 'sync',  //Status
                render: function(data, type, row) {
                    if (row.sync) {
                        return '<span class="badge badge-success">Synced</span>';
                    } else {
                        return '<span class="badge badge-danger">Unsynced</span>';
                    }
                }
            },
            {
                data: 'sync',   //Action
                render: function(data, type, row) {
                    //Clone Data Row
                    var rowClone = JSON.parse(JSON.stringify(row));
                    //Delete nodes
                    delete rowClone.status;
                    delete rowClone.userPrincipalName;
                    //Prepare Data to Send
                    rowClone = Base64.encode(JSON.stringify([rowClone]));
                    var textButton = '', onClickFunction = '', classButton = '';
                    //Evaluate if is posible Sync/Update User according to Email
                    if (row.mail)
                        onClickFunction = "syncUsers(\'" + rowClone + "\')";
                    else
                        onClickFunction = "alertEmailNeeded()";
                    //Update or Sync User
                    if (row.sync) {
                        textButton = "Update";
                        classButton = "btn-info";
                    } else {
                        textButton = "Sync";
                        classButton = "btn-success";
                    }
                    return '<button class="btn ' + classButton + ' col-12" onclick=" ' + onClickFunction + ' " > ' + textButton + ' </button>';
                },
                width: '110px'
            }
        ]
    });
    new $.fn.dataTable.Buttons( table, {
        buttons: [
            {
                text: 'Sync all checked',
                className: 'btn btn-success',
                action: function ( e, dt, node, conf ) {
                    var uSelected = [];
                    dt.data().map((e,i) => {
                        if (e.check) {
                            uSelected.push(e)
                        }
                    })
                    if (uSelected.length > 0) {
                        uSelected = Base64.encode(JSON.stringify(uSelected));
                        syncUsers(uSelected);
                    } else {
                        bootbox.alert('<h5><b>Alert</b></h5><hr>Please select a record to Sync.');
                    }
                }
            }
        ]
    } );
    table.buttons( 0, null ).container().prependTo(
        table.table().container()
    );
}

/**
 * syncUsers
 * Synchronize or Update User(s)
 *
 * @param data (string)  // Data of user(s) in Base64
 * @return none
 *
 * by Telmo Chiri - telmo.chiri@processmaker.com
 */
function syncUsers(data) {
    data = JSON.parse(Base64.decode(data));
    //Get the configuration
    $.ajax({
        'url': '../psAzureAD/controllers/azureADAjax.php',
        'type': 'POST',
        'data': {
            'option': 'syncUsers',
            'data': data
        },
        beforeSend: function(){
            $.LoadingOverlay("show",{
                imageColor: "#2378D4",
                text: "Syncing..."
            });
        },
        success: function (response) {
            var aResponse = JSON.parse(response);
            var messageResponse = '';
            if (aResponse.success)
                messageResponse = '<h5 class="text-success">Success</h5>';
            else
                messageResponse = '<h5 class="text-danger">Error</h5><br>';
            messageResponse += aResponse.data;
            $.LoadingOverlay("hide");
            bootbox.alert(messageResponse, function(){
                connectToAzure('getAzureUsers')
            });
        },
        error: function (e) {
            $.LoadingOverlay("hide");
        },
    });
}

/**
 * alertEmailNeeded
 * Show Alert: "Email is needed to synced." when the email is empty
 *
 * @param inputId (string)  // Data of user(s) in Base64
 * @return none
 *
 * by Telmo Chiri - telmo.chiri@processmaker.com
 */
function alertEmailNeeded(inputId = false) {
    if (inputId) {
        // Uncheck
        document.getElementById(inputId).checked = false;
    }
    bootbox.alert('<h5>Alert</h5><br>Email is needed to synced.');
}

/**
 * checkUser
 * Logical check
 *
 * @param _this (object hmtl)  // Input check
 * @param key (int)  // Key of Object User Data
 * @return none
 *
 * by Telmo Chiri - telmo.chiri@processmaker.com
 */
function checkUser(_this, key) {
    usersData[key].check = _this.checked;
}

/**
 * populateGroupsDataTable
 * Populate Data Table
 *
 * @param data (object)  // Data response
 * @return none
 *
 * by Telmo Chiri - telmo.chiri@processmaker.com
 */
function populateGroupsDataTable(aData) {
    groupsData = aData;
    $('#groupTable').show();
    var table = $('#groupTable').DataTable({
        data: aData,
        destroy: true,
        columns: [
            {
                data: 'check',
                render: function(data, type, row) {
                    if (row.sync) { //Synced (Disable)
                        return '<input type="checkbox" id="chG-' + row.id + '" checked disabled/>';
                    } else {    //Unsynced
                        if (row.check) {
                            return '<input type="checkbox" id="chG-' + row.id + '" class="groupCheckable" checked onclick="checkGroup(this, ' + row.key + ')" />';
                        } else {
                            return '<input type="checkbox" id="chG-' + row.id + '" class="groupCheckable" onclick="checkGroup(this, ' + row.key + ')" />';
                        }
                    }
                }
            },
            {
                data: 'displayName'
            },
            {
                data: 'sync',  //Status
                render: function(data, type, row) {
                    if (row.sync) {
                        return '<span class="badge badge-success">Synced</span>';
                    } else {
                        return '<span class="badge badge-danger">Unsynced</span>';
                    }
                }
            },
            {
                data: 'sync',   //Action
                render: function(data, type, row) {
                    //Clone Data Row
                    var rowClone = JSON.parse(JSON.stringify(row));
                    //Prepare Data to Send
                    rowClone = Base64.encode(JSON.stringify([rowClone]));
                    var textButton = '', onClickFunction = '', classButton = '';
                    onClickFunction = "syncGroups(\'" + rowClone + "\')";
                    //Update or Sync User
                    if (row.sync) {
                        textButton = "Update";
                        classButton = "btn-info";
                    } else {
                        textButton = "Sync";
                        classButton = "btn-success";
                    }
                    return '<button class="btn ' + classButton + ' col-12" onclick=" ' + onClickFunction + ' " > ' + textButton + ' </button>';
                },
                width: '110px'
            }
        ]
    });
    new $.fn.dataTable.Buttons( table, {
        buttons: [
            {
                text: 'Sync all checked',
                className: 'btn btn-success',
                action: function ( e, dt, node, conf ) {
                    var uSelected = [];
                    dt.data().map((e,i) => {
                        if (e.check) {
                            uSelected.push(e)
                        }
                    })
                    if (uSelected.length > 0) {
                        uSelected = Base64.encode(JSON.stringify(uSelected));
                        syncGroups(uSelected);
                    } else {
                        bootbox.alert('<h5><b>Alert</b></h5><hr>Please select a record to Sync.');
                    }
                }
            }
        ]
    } );
    table.buttons( 0, null ).container().prependTo(
        table.table().container()
    );
}

/**
 * checkGroup
 * Logical check
 *
 * @param _this (object hmtl)  // Input check
 * @param key (int)  // Key of Object Group Data
 * @return none
 *
 * by Telmo Chiri - telmo.chiri@processmaker.com
 */
function checkGroup(_this, key) {
    groupsData[key].check = _this.checked;
}

/**
 * syncGroups
 * Synchronize or Update Group(s)
 *
 * @param data (string)  // Data of group(s) in Base64
 * @return none
 *
 * by Telmo Chiri - telmo.chiri@processmaker.com
 */
function syncGroups(data) {
    data = JSON.parse(Base64.decode(data));
    $.ajax({
        'url': '../psAzureAD/controllers/azureADAjax.php',
        'type': 'POST',
        'data': {
            'option': 'syncGroups',
            'data': data
        },
        beforeSend: function(){
            $.LoadingOverlay("show",{
                imageColor: "#2378D4",
                text: "Syncing..."
            });
        },
        success: function (response) {
            var aResponse = JSON.parse(response);
            var messageResponse = '';
            if (aResponse.success)
                messageResponse = '<h5 class="text-success">Success</h5>';
            else
                messageResponse = '<h5 class="text-danger">Error</h5><br>';
            messageResponse += aResponse.data;
            $.LoadingOverlay("hide");
            bootbox.alert(messageResponse, function(){
                connectToAzure('getAzureGroups')
            });
        },
        error: function (e) {
            $.LoadingOverlay("hide");
        }
    });
}

$(document).ready(function () {
    //Populate Configuration
    populateConfiguration();
    //Connect to Azure
    $('#pills-tab a').on('click', function (e) {
        e.preventDefault();
        const {id} = e.target;
        switch (id) {
            case 'pills-settings-tab':
            case 'pills-settings-img':
                populateConfiguration();
                break;
            case 'pills-users-tab':
            case 'pills-users-img':
                connectToAzure('getAzureUsers');
                break;
            case 'pills-groups-tab':
            case 'pills-groups-img':
                connectToAzure('getAzureGroups');
                break;
            default:
                break;
        }
    });
    //Check All Users
    $('#checkAllUsers').click(function () {
        var actualCheck = this.checked;
        usersData.map( (e, i) => {
            if (e.checkable)
                e.check = actualCheck;
        })
        //Refresh Data Table with new Select
        populateUsersDataTable(usersData);
    });
    //Check All Groups
    $('#checkAllGroups').click(function () {
        var actualCheck = this.checked;
        groupsData.map( (e, i) => {
            if (e.checkable)
                e.check = actualCheck;
        })
        //Refresh Data Table with new Select
        populateGroupsDataTable(groupsData);
    });
});