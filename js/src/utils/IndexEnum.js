var IndexEnum = {
    /**
     * @var primary_indexes array to hold 'Primary' index columns.
     */
    primary_indexes: [],

    /**
     * @var unique_indexes array to hold 'Unique' index columns.
     */
    unique_indexes: [],

    /**
     * @var indexes array to hold 'Index' columns.
     */
    indexes: [],

    /**
     * @var fulltext_indexes array to hold 'Fulltext' columns.
     */
    fulltext_indexes: [],

    /**
     * @var spatial_indexes array to hold 'Spatial' columns.
     */
    spatial_indexes: []
};

export default IndexEnum;
