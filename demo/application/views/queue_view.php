<p>
    Fill this out with super interesting things.
</p>
<pre><code>&lt;?php
    $this->asset_manager
        ->add_asset_to_output_queue('js/jquery-1.11.1.min.js', true)
        ->add_asset_to_output_queue('css/basic.css', true);

    echo $this->asset_manager->generate_queue_asset_output();
?></code></pre>