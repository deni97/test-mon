# test-mon

Запускать <code>main.php</code>.

Протестировано всё, кроме двух функций (<code>html_from</code> и <code>get_tabs</code>).
<br>Наличие тестов обусловленно TDD.

Предполагается, что код справится с любым допустимым памятью уровнем вложенности.

Пример запуска:
<br>
<code>
C:\test-mon>php main.php
<ul>
        <li>
                <h1>Группа 1</h1>
                <ul>
                        <li><b>Купите супрадин по цене 100</b></li>
                        <li>
                                <h2>Группа 1.1</h2>
                                <ul>
                                        <li><b>Купите аспирин по цене 200</b></li>
                                </ul>
                        </li>
                        <li>
                                <h2>Группа 1.2</h2>
                                <ul>
                                        <li><b>Покупайте больше UNDEFINED</b></li>
                                        <li>
                                                <h3>Группа 1.2.1</h3>
                                                <ul>
                                                        <li><b>Покупайте больше UNDEFINED</b></li>
                                                </ul>
                                        </li>
                                </ul>
                        </li>
                </ul>
        </li>
</ul>
</code>
