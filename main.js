var layerStyle = new ol.style.Style({
    stroke: new ol.style.Stroke({
        color: 'rgba(0,255,255,0.6)',
        width: 2
    }),
    fill: new ol.style.Fill({
        color: 'rgba(0,200,200,0.1)'
    })
});
var redStyle = new ol.style.Style({
    stroke: new ol.style.Stroke({
        color: 'rgba(255,0,0,0.6)',
        width: 2
    }),
    fill: new ol.style.Fill({
        color: 'rgba(255,0,0,0.1)'
    })
});
var projection = ol.proj.get('EPSG:3857');
var projectionExtent = projection.getExtent();
var size = ol.extent.getWidth(projectionExtent) / 256;
var resolutions = new Array(20);
var matrixIds = new Array(20);
for (var z = 0; z < 20; ++z) {
    // generate resolutions and matrixIds arrays for this WMTS
    resolutions[z] = size / Math.pow(2, z);
    matrixIds[z] = z;
}
var popup = new ol.Overlay.Popup();
var lastFeature;

/*
 * layer
 * EMAP2: 臺灣通用電子地圖透明
 * EMAP6: 臺灣通用電子地圖(不含等高線)
 * EMAP7: 臺灣通用電子地圖EN(透明)
 * EMAP8: Taiwan e-Map
 * PHOTO2: 臺灣通用正射影像
 * ROAD: 主要路網
 */
var baseLayer = new ol.layer.Tile({
    source: new ol.source.WMTS({
        matrixSet: 'EPSG:3857',
        format: 'image/png',
        url: 'http://maps.nlsc.gov.tw/S_Maps/wmts',
        layer: 'EMAP',
        tileGrid: new ol.tilegrid.WMTS({
            origin: ol.extent.getTopLeft(projectionExtent),
            resolutions: resolutions,
            matrixIds: matrixIds
        }),
        style: 'default',
        wrapX: true,
        attributions: '<a href="http://maps.nlsc.gov.tw/" target="_blank">國土測繪圖資服務雲</a>'
    }),
    opacity: 0.8
});

var mapLayers = [baseLayer];
var cityLayer, cityList;
$.getJSON('json/city_list.json', function(r) {
  cityList = r;
  cityLayer = new ol.layer.Vector({
      source: new ol.source.Vector({
          url: 'json/city.topo.json',
          format: new ol.format.TopoJSON()
      }),
      style: function(f) {
        var p = f.getProperties(), targetCode = '';
        switch(p.COUNTYCODE) {
          case '10018':
          case '10020':
            targetCode = p.COUNTYCODE;
            break;
          default:
            targetCode = p.TOWNCODE;
        }
        if(cityList[targetCode]) {
          return redStyle;
        }
        return layerStyle;
      }
  });
  map.addLayer(cityLayer);
});

var map = new ol.Map({
    layers: mapLayers,
    target: 'map',
    controls: ol.control.defaults({
        attributionOptions: /** @type {olx.control.AttributionOptions} */ ({
            collapsible: false
        })
    }),
    view: new ol.View({
        center: ol.proj.fromLonLat([121, 24]),
        zoom: 10
    })
});
map.addOverlay(popup);
map.on('singleclick', onLayerClick);
map.on('pointermove', onPointerMove);

function onPointerMove(e) {
    map.forEachFeatureAtPixel(e.pixel, function (feature, layer) {
        var p = feature.getProperties();
        if (p.COUNTYCODE) {
            $('.navbar-text').html(p.COUNTYNAME + ' > ' + p.TOWNNAME);
        }
    });
};

function onLayerClick(e) {
    var hasFeature = false;
    map.forEachFeatureAtPixel(e.pixel, function (feature, layer) {
        var p = feature.getProperties();
        if (p.COUNTYCODE) {
            var targetCode = '';
            $('.navbar-text').html(p.COUNTYNAME + ' > ' + p.TOWNNAME);
            switch(p.COUNTYCODE) {
              case '10018':
              case '10020':
                targetCode = p.COUNTYCODE;
                break;
              default:
                targetCode = p.TOWNCODE;
            }
            targetLayer = new ol.layer.Vector({
                source: new ol.source.Vector({
                    url: 'json/city/' + targetCode + '.json',
                    format: new ol.format.TopoJSON()
                }),
                style: layerStyle
            });
            targetLayer.on('change', function () {
                if (targetLayer.getSource().getState() === 'ready') {
                  cityLayer.setVisible(false);
                  map.getView().setCenter(e.coordinate);
                  map.getView().setZoom(12);
                }
            });
            map.addLayer(targetLayer);
        } else {
          if(lastFeature) {
            lastFeature.setStyle(layerStyle);
          }
          feature.setStyle(redStyle);
          var message = '';
          for(k in p) {
            switch(k) {
              case 'geometry':
              break;
              case '所有權人':
                message += '<h4>' + k + '</h4>';
                for(ok in p[k]) {
                  message += ok + ': ' + p[k][ok] + '<br />';
                }
              break;
              default:
                message += k + ': ' + p[k] + '<br />';
            }
          }
          popup.show(e.coordinate, message);
          map.getView().setCenter(e.coordinate);
          lastFeature = feature;
        }
        hasFeature = true;
    });
    if (false === hasFeature) {
        cityLayer.setVisible(true);
        map.getView().setZoom(12);
        targetLayer.setVisible(false);
        popup.hide();
    }
}
