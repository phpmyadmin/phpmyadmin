    </td>
  </tr>
</table>
</body>
</html>
<?php
if (isset($cfgOBGzip) && isset($ob_mode))
  out_buffer_post($ob_mode);
?>
