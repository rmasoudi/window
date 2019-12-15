
function addProduct(item) {
    var products = getProducts();
    products.push(item);
    setProducts(products);
}

function getProducts() {
    var productsStr = localStorage.getItem("products");
    var products;
    if (productsStr === null || productsStr === undefined) {
        products = [];
    }
    else {
        products = JSON.parse(productsStr);
    }
    return products;
}
function setProducts(products) {
    localStorage.setItem("products", JSON.stringify(products));
}

function removeProduct(index) {
    var products = getProducts();
    products.splice(index, 1);
    setProducts(products);
}
function editProduct(index, item) {
    var products = getProducts();
    products[index] = item;
    setProducts(products);
}

function setProductUrl(index, pageIndex, url) {
    var products = getProducts();
    var product = products[index];
    var uploads;
    if (product.uploads === null || product.uploads === undefined) {
        uploads = [];
    }
    else {
        uploads = product.uploads;
    }
    uploads[pageIndex] = url;
    product.uploads = uploads;
    products[index] = product;
    setProducts(products);
}

function setEditing(index, product) {
    if (index === -1) { // clear editing item
        localStorage.removeItem("editing");
        return;
    }
    var pair = {
        index: index,
        product: product
    };
    localStorage.setItem("editing", JSON.stringify(pair));
}

function getEditing() {
    var str = localStorage.getItem("editing");
    if (str === null || str === undefined) {
        return null;
    }
    return JSON.parse(localStorage.getItem("editing"));
}

function setAddress(index, item) {
    var obj = {
        index: index,
        item: item
    };
    localStorage.setItem("address", JSON.stringify(obj));
}

function getAddress() {
    if (localStorage.getItem("address") === null) {
        return null;
    }
    return JSON.parse(localStorage.getItem("address"));
}

function setCenter(index, item) {
    var obj = {
        index: index,
        item: item
    };
    localStorage.setItem("center", JSON.stringify(obj));
}

function getCenter() {
    if (localStorage.getItem("center") === null) {
        return null;
    }
    return JSON.parse(localStorage.getItem("center"));
}
function isFreeCarrier(center, dist) {
    if (center.carrier === "0") {
        return false;
    }
    if (center.carrier === "7") {
        return true;
    }
    var range = 0;
    switch (center.carrier) {
        case "1":
            range = 3;
            break;
        case "2":
            range = 6;
            break;
        case "3":
            range = 10;
            break;
        case "4":
            range = 20;
            break;
        case "5":
            range = 30;
            break;
        case "6":
            range = 50;
            break;
    }
    return dist <= range;
}
function getDistance(loc, point) {
    var array = loc.split(",");
    lat1 = parseFloat(array[0]);
    lon1 = parseFloat(array[1]);
    array = point.split(",");
    lat2 = parseFloat(array[0]);
    lon2 = parseFloat(array[1]);
    if ((lat1 == lat2) && (lon1 == lon2)) {
        return 0;
    }
    else {
        var radlat1 = Math.PI * lat1 / 180;
        var radlat2 = Math.PI * lat2 / 180;
        var theta = lon1 - lon2;
        var radtheta = Math.PI * theta / 180;
        var dist = Math.sin(radlat1) * Math.sin(radlat2) + Math.cos(radlat1) * Math.cos(radlat2) * Math.cos(radtheta);
        if (dist > 1) {
            dist = 1;
        }
        dist = Math.acos(dist);
        dist = dist * 180 / Math.PI;
        dist = dist * 60 * 1.1515;
        return dist * 1.609344;
    }
}
function getSelectedLang() {
    var products = getProducts();
    var val;
    for (var i = 0; i < products.length; i++) {
        val = products[i].lang;
        break;
    }
    return getLangByCode(val);
}

function getLangByCode(val) {
    switch (val) {
        case "1":
            return "انگلیسی";
        case "2":
            return "فرانسوی";
        case "3":
            return "آلمانی";
        case "4":
            return "اسپانیایی";
        case "5":
            return "چینی";
        case "6":
            return "عربی";
        case "7":
            return "اردو";
        case "8":
            return "ژاپنی";
        case "9":
            return "ترکی استانبولی";
        case "10":
            return "ترکی";
        case "11":
            return "ایتالیایی";
        case "12":
            return "روسی";
        case "13":
            return "ارمنی";
        case "14":
            return "کردی";

    }
    return "";
}

function stepClicked(e) {
    var item = $(e.target);
    var id = "";
    if (item.prop("tagName") === "DIV" && item.hasClass("done") && item.hasClass("circle")) {
        id = item.prop("id");
    }
    else if (item.prop("tagName") === "SPAN" && item.parent().hasClass("done") && item.parent().hasClass("circle")) {
        id = item.parent().prop("id");
    }
    var loc = "";
    switch (id) {
        case "step1":
            loc = "انتخاب_مدارک_ترجمه_رسمی";
            break;
        case "step2":
            loc = "پیوست_مدارک_ترجمه_رسمی";
            break;
        case "step3":
            loc = "تعیین_آدرس";
            break;
        case "step4":
            loc = "لیست_دفاتر_ترجمه_رسمی";
            break;
        case "step5":
            loc = "صورتحساب";
            break;
    }
    if (loc !== "") {
        window.location = loc;
    }
}