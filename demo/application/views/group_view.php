<p>
    Fill this out with super interesting things.
</p>
<pre><code>&lt;?php
    echo $this->asset_manager->generate_asset_tag('css/basic.css');
    echo $this->asset_manager->generate_asset_tag('js/jquery-1.11.1.min.js');
    echo $this->asset_manager->generate_logical_group_asset_output('noty');
    echo $this->asset_manager->generate_asset_tag('js/noty-woot.js');
?></code></pre>