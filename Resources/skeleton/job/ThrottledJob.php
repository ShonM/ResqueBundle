<?php

namespace {{ namespace }}\Job;

{% block use_statements %}
use ShonM\ResqueBundle\Job\JobInterface;
use ShonM\ResqueBundle\Annotation\Throttled;
{% endblock use_statements %}

{% block class_definition %}
/**
 * @Throttled(canRunEvery=30)
 */
class {{ job }}Job implements JobInterface
{% endblock class_definition %}
{
{% block class_body %}
    public function perform()
    {
        // create job
    }
{% endblock class_body %}
}
