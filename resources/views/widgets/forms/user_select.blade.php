var data = [
    {!! isset($user) ? $user->toJson() : '' !!}
];
$('.user-search-select').select2({
    ajax: {
        url: '/admin/users/select-search',
        dataType: 'json',
    },
    data: data,
    placeholder: 'Enter email, mobile or account to search',
    minimumInputLength: 2,
    escapeMarkup: function (markup) { return markup; },
    templateResult: formatUserSelection,
    templateSelection: formatUserSelection,
});


function formatUserSelection (user) {
    if (!user.id && user.text) {
        return user.text;
    }
    var wrapper = $('<div/>');
    var upper = $('<div/>').text((user.name || ' - ') + " (" + user.id + ")");
    var lower = $('<div/>');
    var account = $('<span/>')
        .addClass("d-inline mr-3")
        .html('<i class="zmdi zmdi-account-box"></i> ' + user.account);
    var email = $('<span/>')
        .addClass("d-inline mr-3")
        .html((user.is_email_verified ? '<i class="zmdi zmdi-check"></i> ' : '') + '<i class="zmdi zmdi-email"></i> ' + (user.email || ' - '));
    var mobile = $('<span/>')
        .addClass("d-inline mr-3")
        .html((user.is_mobile_verified ? '<i class="zmdi zmdi-check"></i> ' : '') + '<i class="zmdi zmdi-smartphone"></i> ' + (user.moblie || ' - '));
    var verification = $('<span/>')
        .addClass("d-inline")
        .html(user.is_verified ? '<i class="zmdi zmdi-face"></i> 已通過實名驗證' : '<i class="zmdi zmdi-help-outline"></i> 未實名驗證');

    lower.append(account).append(email).append(mobile).append(verification);
    wrapper.append(upper).append(lower);
    return wrapper.prop('outerHTML');
}
