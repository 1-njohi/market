In that same style and theme, I want you to create a BetslipsTable component that will take in a props of betslips. The table should be mobile first. The prop betslips will be an array of betslips with the structure I'll give at the end. It should also have a button, or some way to navigate to a betslip on the route "betslip/view/g/${betslip.code}". The component will go into the seller dashboard, I just need that single component though. Below is the data structure:



      array (
        0 => 
        array (
          'id' => '7181761e-27e8-4b14-a545-866efdf61210',
          'code' => 'PRL-Z2TB-EC5',
          'legs' => 1,
          'total_odds' => 1.59,
          'price' => 50.0,
          'remaining' => 1,
          'status' => 'pending',
          'created_at' => '2026-07-06T12:50:04.000000Z',
          'days_active' => 0.614179036238426,
          'is_expiring_soon' => false,
          'purchases' => 0,
        ),
        1 => 
        array (
          'id' => '2b040722-56d3-43be-aa38-e4635d60b81c',
          'code' => 'SWZ-JKHZ-V1K',
          'legs' => 3,
          'total_odds' => 1823.51,
          'price' => 50.0,
          'remaining' => 3,
          'status' => 'pending',
          'created_at' => '2026-07-06T11:34:42.000000Z',
          'days_active' => 0.6665170030902777,
          'is_expiring_soon' => false,
          'purchases' => 0,
        ),
      ),